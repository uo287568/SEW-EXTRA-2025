import xml.etree.ElementTree as ET

def generar_SVG(archivo_xml):
    tree = ET.parse(archivo_xml)
    root = tree.getroot()

    for index, ruta in enumerate(root.findall('.//ruta')):
        altitudes = [float(coord.attrib['altitud-metros']) for coord in ruta.findall('.//coordenadas')]
        distancias = [float(hito.find('distancia-hito').text) for hito in ruta.findall('.//hito')]

        max_altitude = max(altitudes)
        max_distance = max(distancias)

        vertical_offset = 80  # Espacio superior aumentado para texto
        left_margin = 60      # Margen izquierdo aumentado para el nombre del lugar de inicio

        scaled_altitudes = [vertical_offset + (100 - (a * 100 / max_altitude)) for a in altitudes]
        scaled_distances = [d * 100 / max_distance for d in distancias]

        base_altura = max(scaled_altitudes)

        lugar_inicio = ruta.find('./detalles-ruta/lugar-inicio/lugar').text.strip()

        with open(f'perfil{index + 1}.svg', 'w', encoding='utf-8') as svg_file:
            total_width = 1000
            svg_file.write('<?xml version="1.0" encoding="UTF-8" ?>\n')
            svg_file.write(f'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="{int(base_altura) + 50}" width="{total_width}">\n')

            polyline_points = []
            current_x = left_margin

            polyline_points.append(f'{current_x},{scaled_altitudes[0]}')

            for i, distancia in enumerate(scaled_distances):
                current_x += distancia
                polyline_points.append(f'{current_x},{scaled_altitudes[i + 1]}')

            polyline_points.append(f'{current_x},{base_altura}')
            polyline_points.append(f'{left_margin},{base_altura}')
            polyline_points.append(f'{left_margin},{scaled_altitudes[0]}')

            polyline = ' '.join(polyline_points)
            svg_file.write(f'<polyline points="{polyline}" stroke="black" stroke-width="2" fill="lightblue" />\n')

            zero_y = vertical_offset + (100 - (0 * 100 / max_altitude))
            svg_file.write(f'<line x1="0" y1="{zero_y}" x2="{current_x + 20}" y2="{zero_y}" stroke="red" stroke-dasharray="4" />\n')
            svg_file.write(f'<text x="5" y="{zero_y - 5}" font-size="10" fill="red">Cota 0 m</text>\n')

            # Ajuste: lugar de inicio con margen izquierdo y alineado a la izquierda
            texto_inicio_x = left_margin
            texto_inicio_y = scaled_altitudes[0] - 10
            svg_file.write(
                f'<text x="{texto_inicio_x}" y="{texto_inicio_y}" font-size="10" text-anchor="start" '
                f'transform="rotate(-45 {texto_inicio_x},{texto_inicio_y})">{lugar_inicio}</text>\n'
            )

            current_x = left_margin
            for i, hito in enumerate(ruta.findall('.//hito')):
                distancia = scaled_distances[i]
                current_x += distancia
                nombre_hito = hito.find('nombre').text.strip()

                y_offset = -20 if i % 2 == 0 else -35
                texto_y = scaled_altitudes[i + 1] + y_offset

                svg_file.write(
                    f'<text x="{current_x}" y="{texto_y}" font-size="10" text-anchor="middle" '
                    f'transform="rotate(-45 {current_x},{texto_y})">{nombre_hito}</text>\n'
                )

            svg_file.write('</svg>')

def main():
    miArchivoXML = input('Introduzca un archivo XML = ')
    generar_SVG(miArchivoXML)

if __name__ == "__main__":
    main()
