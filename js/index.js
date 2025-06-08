class Index {
    carrusel() {
        const slides = document.querySelectorAll("img");

        const nextSlide = document.querySelector("button[data-action='next']");
        
        var curSlide = 7;
        var maxSlide = slides.length - 1;

        nextSlide.addEventListener("click", function () {
            if (curSlide === maxSlide) {
                curSlide = 0;
            } else {
                curSlide++;
            }

            slides.forEach((slide, indx) => {
                var trans = 100 * (indx - curSlide);
                $(slide).css('transform', 'translateX(' + trans + '%)')
            });
        });

        const prevSlide = document.querySelector("button[data-action='prev']");
        
        prevSlide.addEventListener("click", function () {
            if (curSlide === 0) {
                curSlide = maxSlide;
            } else {
                curSlide--;
            }

            slides.forEach((slide, indx) => {
                var trans = 100 * (indx - curSlide);
                $(slide).css('transform', 'translateX(' + trans + '%)')
            });
        });
    }
    cargaNoticias() {
        const apiKey = 'd9888c830e7d43968a5624019c1863d5';
        const query = '"San Tirso de Abres" OR Vegadeo';
        const apiUrl = `https://api.worldnewsapi.com/search-news?text=${encodeURIComponent(query)}&language=es&api-key=${apiKey}`;

        $.ajax({
            url: apiUrl,
            method: 'GET',
            success: function(data) {
                const articulos = data.news.slice(0, 6);

                const noticiasSection = $("body > section");
                noticiasSection.children().not("h3").remove();

                if (articulos.length > 0) {
                    articulos.forEach(articulo => {
                        const titulo = articulo.title;
                        const texto = articulo.text || '';
                        const resumen = texto.length > 250 ? texto.substring(0, 250) + '...' : texto;
                        const fecha = new Date(articulo.publish_date).toLocaleString();

                        const item = $('<article>')
                            .append($('<h3>').text(titulo))
                            .append($('<p>').text(resumen))
                            .append($('<p>').text(`Fecha de publicación: ${fecha}`));

                        noticiasSection.append(item);
                    });
                } else {
                    noticiasSection.append($('<p>').text('No se encontraron noticias.'));
                }
            },
            error: function(error) {
                console.error('Error al obtener noticias:', error);

                const noticiasSection = $("body > section");
                noticiasSection.children().not("h3").remove();
                noticiasSection.append($('<p>').text('Error al cargar noticias.'));
            }
        });
    }
}