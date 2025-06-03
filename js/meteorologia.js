class Meteorologia {
    obtenerPronosticoMeteorologico() {
        const apiKey = 'd560fe35de1b4d4ef5279bbc32bda158';
        const lat = 43.4022489;
        const lon = -7.1361848;
        const apiUrl = `https://api.openweathermap.org/data/3.0/onecall?lat=${lat}&lon=${lon}&exclude=minutely,hourly,alerts&appid=${apiKey}&units=metric`;
    
        $.get(apiUrl, (data) => {
            const datosPronostico = data.daily.slice(0,7);
    
            const table = $('<table>').appendTo('article');
            table.append('<caption>Pronóstico meteorológico de 7 días</caption>');

            const thead = $('<thead>').appendTo(table);
            const headRow = $('<tr>').appendTo(thead);
            $('<th id="icono" scope="coL">').html('Icono del tiempo').appendTo(headRow);
            $('<th id="info" scope="col">').html('Información precisa').appendTo(headRow);
    
            const tbody = $('<tbody>').appendTo(table);

            datosPronostico.forEach(dia => {
                const fecha = new Date(dia.dt * 1000).toLocaleDateString();
                const temperatura = dia.temp.day;
                const temperaturaMax = dia.temp.max;
                const temperaturaMin = dia.temp.min;
                const humedad = dia.humidity;
                const lluvia = dia.rain ?? 0;
                const iconoTiempo = `http://openweathermap.org/img/wn/${dia.weather[0].icon}.png`;

                const row1 = $('<tr>').appendTo(table);
                $('<td rowspan="6" headers="icono">').html(`<img src="${iconoTiempo}" alt="Weather icon">`).appendTo(row1);
                $('<td headers="info">').html(fecha).appendTo(row1);

                const row2 = $('<tr>').appendTo(table);
                $('<td headers="info">').html(`Temperatura: ${temperatura}&deg;C`).appendTo(row2);

                const row3 = $('<tr>').appendTo(table);
                $('<td headers="info">').html(`Temperatura máxima: ${temperaturaMax}&deg;C`).appendTo(row3);

                const row4 = $('<tr>').appendTo(table);
                $('<td headers="info">').html(`Temperatura mínima: ${temperaturaMin}&deg;C`).appendTo(row4);

                const row5 = $('<tr>').appendTo(table);
                $('<td headers="info">').html(`Humedad: ${humedad}%`).appendTo(row5);

                const row6 = $('<tr>').appendTo(table);
                $('<td headers="info">').html(`Lluvia: ${lluvia} mm</p>`).appendTo(row6);
            });
        });
    }
}