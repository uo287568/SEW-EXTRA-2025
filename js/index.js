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
        const apiKey = '0b113d202480498e93fb38db6763160a';
        const query = 'San Tirso de Abres OR Taramundi OR Vegadeo';
        
        const apiUrl = `https://newsapi.org/v2/everything?q=${query}&apiKey=${apiKey}`;
        
        $.ajax({
            url: apiUrl,
            method: 'GET',
            success: function(data) {
                const articulos = data.articles.slice(0, 6);
                if (articulos.length > 0) {
                    const noticiasContainer = $('aside');
                    noticiasContainer.empty();

                    articulos.forEach(articulo => {
                        const titulo = articulo.title;
                        const descripcion = articulo.description;
                        const fechaPulicacion = new Date(articulo.publishedAt).toLocaleString();

                        const item = $('<article>').append($('<h3>').text(titulo))
                            .append($('<p>').text(descripcion)).append($('<p>')
                            .text(`Última modificación el ${fechaPulicacion}`));
                        
                        noticiasContainer.append(item);
                    });
                } else {
                    $('aside').text('Nos e encontraron noticias');
                }
            },
            error: function(error) {
                console.error('Error al obtener noticias:', error);
            }
        });
        
    }
}