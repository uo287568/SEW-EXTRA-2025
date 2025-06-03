import xml.etree.ElementTree as ET

def generar_KML(archivoXML):
    try:
        arbol = ET.parse(archivoXML)
    except IOError:
        print('No se encuentra el archivo ', archivoXML)
        exit()
    except ET.ParseError:
        print("Error procesando en el archivo XML = ", archivoXML)
        exit()

    raiz = arbol.getroot()

    # Contador para nombrar los archivos KML
    contador = 1

    for ruta in raiz.findall('.//ruta'):
        kml = ET.Element("kml", xmlns="http://www.opengis.net/kml/2.2")
        document = ET.Element("Document")
        name = ET.Element("name")
        name.text = "Ruta " + str(contador)

        document.append(name)

        coordinates_list = []  # Lista para almacenar las coordenadas de la ruta

        for coordenadas in ruta.findall('.//coordenadas'):
            longitud = 0
            latitud = 0
            altitud = 0
            for a in coordenadas.attrib:
                if a == "longitud":
                    longitud = coordenadas.attrib[a]
                if a == "latitud":
                    latitud = coordenadas.attrib[a]
                if a == "altitud-metros":
                    altitud = coordenadas.attrib[a]
            coordinates = longitud + "," + latitud + "," + altitud
            coordinates_list.append(coordinates)

            placemark = ET.Element("Placemark")
            point = ET.Element("Point")
            coordinates_element = ET.Element("coordinates")
            coordinates_element.text = coordinates
            point.append(coordinates_element)
            placemark.append(point)

            document.append(placemark)

        # Crear una línea que conecta las coordenadas
        if coordinates_list:
            line_placemark = ET.Element("Placemark")
            line = ET.Element("LineString")
            coordinates_element = ET.Element("coordinates")
            coordinates_element.text = " ".join(coordinates_list)  # Unir las coordenadas en una cadena
            line.append(coordinates_element)
            line_placemark.append(line)
            document.append(line_placemark)

        kml.append(document)

        with open(f"ruta{contador}.kml", "wb") as kml_file:
            kml_data = ET.tostring(kml)
            kml_file.write(kml_data)

        contador += 1

def main():
    miArchivoXML = input('Introduzca un archivo XML = ')
    generar_KML(miArchivoXML)

if __name__ == "__main__":
    main()
