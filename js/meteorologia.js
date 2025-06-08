class Meteorologia {
    obtenerPronosticoMeteorologico() {
        const apiKey = 'd560fe35de1b4d4ef5279bbc32bda158';
        const lat = 43.4022489;
        const lon = -7.1361848;
        const apiUrl = `https://api.openweathermap.org/data/3.0/onecall?lat=${lat}&lon=${lon}&exclude=minutely,hourly,alerts&appid=${apiKey}&units=metric`;
    
        $.get(apiUrl, (data) => {
            const datosPronostico = data.daily.slice(0,7);
    
            const listaDias = $('<ul>').appendTo('main');

            datosPronostico.forEach(dia => {
                const fecha = new Date(dia.dt * 1000).toLocaleDateString();
                const temperatura = dia.temp.day;
                const temperaturaMax = dia.temp.max;
                const temperaturaMin = dia.temp.min;
                const humedad = dia.humidity;
                const lluvia = dia.rain ?? 0;
                const iconoTiempo = `https://openweathermap.org/img/wn/${dia.weather[0].icon}.png`;
                const descripcionClima = dia.weather[0].description;

                const itemDia = $('<li>').appendTo(listaDias);
                $('<img>').attr('src', iconoTiempo).attr('alt', `Icono de tiempo: ${descripcionClima}`).appendTo(itemDia);

                const listaDetalles = $('<ul>').appendTo(itemDia);
                $('<li>').text(`Fecha: ${fecha}`).appendTo(listaDetalles);
                $('<li>').text(`Temperatura: ${temperatura}°C`).appendTo(listaDetalles);
                $('<li>').text(`Temperatura máxima: ${temperaturaMax}°C`).appendTo(listaDetalles);
                $('<li>').text(`Temperatura mínima: ${temperaturaMin}°C`).appendTo(listaDetalles);
                $('<li>').text(`Humedad: ${humedad}%`).appendTo(listaDetalles);
                $('<li>').text(`Lluvia: ${lluvia} mm`).appendTo(listaDetalles);
            });

        });
    }
}