class Rutas {
    constructor(){
        this.mapbox_token = 'pk.eyJ1IjoidW8yODc1NjgiLCJhIjoiY2x3MjgzNzljMGtoNDJpbXU5ZWRleGRsayJ9.QGPrB_BeCs5_SKDc6-U6hw';
        this.init();
    }

    init() {
        $('input:eq(0)').on('change', (e) =>  {
            var archivoXML = e.target.files[0];
            this.handleXMLFile(archivoXML);
        });
    }

    convertKmlToGeoJSON(kmlDoc) {
        const geojson = {
            type: 'FeatureCollection',
            features: []
        };
    
        const placemarks = kmlDoc.querySelectorAll('Placemark');
    
        placemarks.forEach(placemark => {
            const point = placemark.querySelector('Point');
            const lineString = placemark.querySelector('LineString');
    
            if (point) {
                const coordinates = point.querySelector('coordinates').textContent.split(',').map(coord => parseFloat(coord));
                geojson.features.push({
                    type: 'Feature',
                    geometry: {
                        type: 'Point',
                        coordinates: coordinates
                    },
                    properties: {}
                });
            }
    
            if (lineString) {
                const coordinatesString = lineString.querySelector('coordinates').textContent;
                const coordinatesArray = coordinatesString.split(' ').map(coordString => coordString.split(',').map(coord => parseFloat(coord)));
                geojson.features.push({
                    type: 'Feature',
                    geometry: {
                        type: 'LineString',
                        coordinates: coordinatesArray
                    },
                    properties: {}
                });
            }
        });
    
        return geojson;
    }
    
    handleXMLFile(file) {
        const reader = new FileReader();

        reader.onload = (event) => {
            const xmlString = event.target.result;
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlString, 'text/xml');

            const fileInfoHTML = this.buildFileInfo(xmlDoc);
            $('article').html(fileInfoHTML);

            this.crearMapaDinamico();
            this.insertarAltimetrias();
        };

        reader.readAsText(file);
    }
    
    buildFileInfo(xmlDoc) {
        const rutas = xmlDoc.getElementsByTagName('ruta');
        this.kmls = [];
        var fileInfoHTML = '<h3>Información de las rutas disponibles:</h3>';

        for (let i = 0; i < rutas.length; i++) {
            const ruta = rutas[i];
            const nombreRuta = ruta.getElementsByTagName('nombre')[0].textContent;
            const tipoRuta = ruta.getAttribute('type');
            const recomendacionRuta = ruta.getAttribute('recomendación');
            const transporte = ruta.getElementsByTagName('transporte')[0].textContent;
            const personasAdecuadas = ruta.getElementsByTagName('personas-adecuadas')[0].textContent;
            const fechaInicio = ruta.getElementsByTagName('fecha-inicio')[0].textContent;
            const horaInicio = ruta.getElementsByTagName('hora-inicio')[0].textContent;
            const duracion = ruta.getElementsByTagName('detalles-ruta')[0].getAttribute('duración-horas');
            
            fileInfoHTML += `<article>`;
            fileInfoHTML += `<h4>${nombreRuta}</h4>`;
            fileInfoHTML += `<p>Tipo: ${tipoRuta}, Recomendación: ${recomendacionRuta}</p>`;
            fileInfoHTML += `<p>Transporte: ${transporte}</p>`;
            fileInfoHTML += `<p>Personas adecuadas: ${personasAdecuadas}</p>`;
            fileInfoHTML += `<p>Fecha y hora de inicio: ${fechaInicio}, ${horaInicio}</p>`;
            fileInfoHTML += `<p>Duración: ${duracion} horas</p>`;

            const acciones = ruta.getElementsByTagName('acción');
            fileInfoHTML += `<h5>Descripción:</h5>`;
            fileInfoHTML += `<ul>`;
            for (let q = 0; q < acciones.length; q++) {
                const accion = acciones[q];
                const nombreAccion = accion.textContent;
                fileInfoHTML += `<li>${nombreAccion}</li>`;
            }
            fileInfoHTML += `</ul>`;

            const elementos = ruta.getElementsByTagName('elemento');
            fileInfoHTML += `<h5>Elementos:</h5>`;
            fileInfoHTML += `<ul>`;
            for (let j = 0; j < elementos.length; j++) {
                const elemento = elementos[j];
                const nombreElemento = elemento.textContent;
                const incluido = elemento.getAttribute('incluido');
                const incluidoText = incluido === 'Si' ? 'Incluido' : 'No incluido';
                fileInfoHTML += `<li>${nombreElemento} - ${incluidoText}</li>`;
            }
            fileInfoHTML += `</ul>`;

            const lugarInicio = ruta.getElementsByTagName('lugar')[0].textContent;
            fileInfoHTML += `<p>Lugar de inicio: ${lugarInicio}</p>`;

            const hitos = ruta.getElementsByTagName('hito');
            fileInfoHTML += `<h5>Hitos:</h5>`;
            for (let k = 0; k < hitos.length; k++) {
                const hito = hitos[k];
                const nombreHito = hito.getElementsByTagName('nombre')[0].textContent;
                const descripcionHito = hito.getElementsByTagName('descripción-hito')[0].textContent;

                fileInfoHTML += `<article>`;
                fileInfoHTML += `<h6>${nombreHito}</h6>`;
                fileInfoHTML += `<p>${descripcionHito}</p>`;

                const fotosHito = hito.getElementsByTagName('foto');
                for (let l = 0; l < fotosHito.length; l++) {
                    const fotoHito = fotosHito[l].textContent;
                    fileInfoHTML += `<img src="${fotoHito}" alt="${nombreHito} Foto ${l + 1}" />`;
                }

                fileInfoHTML += `</article>`;
            }
            fileInfoHTML += `<h5>Planimetría de la ruta:</h5>`;
            fileInfoHTML += `<aside></aside>`;
            const kmlTexto = ruta.getElementsByTagName('kml')[0].textContent.trim();
            this.kmls.push(kmlTexto);

            const svgRuta = ruta.getElementsByTagName('svg')[0]?.textContent.trim();
            this.svgs = this.svgs || [];
            this.svgs.push(svgRuta);

            fileInfoHTML += `</article>`;
        }

        return fileInfoHTML;
    }

    crearMapaDinamico() {
        mapboxgl.accessToken = this.mapbox_token;

        const asides = document.querySelectorAll('aside');

        asides.forEach((aside, i) => {
            const mapa = new mapboxgl.Map({
                container: aside,
                style: 'mapbox://styles/mapbox/streets-v11',
                zoom: 14
            });

            mapa.addControl(new mapboxgl.NavigationControl());
            mapa.addControl(new mapboxgl.ScaleControl({
                maxWidth: 100,
                unit: 'metric'
            }));

            // Ruta del archivo KML para esta ruta
            const kmlPath = this.kmls[i];

            // Cargar archivo KML con fetch y luego procesar
            fetch(kmlPath)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`No se pudo cargar el archivo KML: ${kmlPath}`);
                    }
                    return response.text();
                })
                .then(kmlText => {
                    const parser = new DOMParser();
                    const kmlDoc = parser.parseFromString(kmlText, 'text/xml');
                    const geojson = this.convertKmlToGeoJSON(kmlDoc);

                    mapa.on('load', () => {
                        if (mapa.getSource('route')) {
                            mapa.removeLayer('route');
                            mapa.removeSource('route');
                        }

                        mapa.addSource('route', {
                            type: 'geojson',
                            data: geojson
                        });

                        mapa.addLayer({
                            id: 'route',
                            type: 'line',
                            source: 'route',
                            layout: {
                                'line-join': 'round',
                                'line-cap': 'round'
                            },
                            paint: {
                                'line-color': '#FF0000',
                                'line-width': 6
                            }
                        });

                        // Centrar el mapa según las coordenadas de la ruta (primer feature)
                        if (geojson.features.length > 0) {
                            const coords = geojson.features[0].geometry.coordinates;
                            mapa.flyTo({ center: coords, zoom: 13 });
                        }
                    });
                })
                .catch(error => {
                    console.error(error);
                });
        });
    }

    insertarAltimetrias() {
        const mainArticle = document.querySelector('main > article');
        if (!mainArticle) return;

        const rutaArticles = mainArticle.querySelectorAll(':scope > article');

        this.svgs.forEach((svgPath, i) => {
            if (!svgPath) return;

            fetch(svgPath)
                .then(response => {
                    if (!response.ok) throw new Error(`No se pudo cargar el SVG: ${svgPath}`);
                    return response.text();
                })
                .then(svgText => {
                    const tempContainer = document.createElement('template');
                    tempContainer.innerHTML = svgText.trim();

                    const svgElement = tempContainer.content.querySelector('svg');
                    if (!svgElement) return;

                    const article = rutaArticles[i];
                    if (!article) return;

                    const aside = article.querySelector('aside');
                    if (!aside) return;

                    const titulo = document.createElement('h5');
                    titulo.textContent = 'Altimetría de la ruta:';

                    // Insertar título y SVG justo después del aside
                    aside.insertAdjacentElement('afterend', svgElement);
                    svgElement.insertAdjacentElement('beforebegin', titulo);
                })
                .catch(error => console.error(error));
        });
    }

}