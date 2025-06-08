class Juego {
    constructor() {
        this.questions = [
            {
                question: "¿Cuál de los siguientes NO es un plato típico de San Tirso de Abres?",
                options: ["Fabada asturiana", "Pasta carbonara", "Cachopo", "Salmón del río Eo", "Pote asturiano"],
                answer: 1
            },
            {
                question: "¿Qué elementos se muestran en la página principal?",
                options: ["Carrusel y Noticias", "Carrusel y Meteorología", "Rutas y Meteorología", "Noticias y Rutas", "Gastronomía y Noticias"],
                answer: 0
            },
            {
                question: "¿Cuál de los siguientes NO es un ingrediente de San Tirso de Abres?",
                options: ["Fabas", "Embutidos", "Torreznos", "Quesos", "Carne de ternera"],
                answer: 2
            },
            {
                question: "¿Qué nombre tiene una de las rutas por el concejo?",
                options: ["Ruta de las Xanas", "Senda del Oso", "Ruta de los Arrieiros", "Senda Verde", "Ruta del Alba"],
                answer: 2
            },
            {
                question: "¿De qué receta se muestra un vídeo en el apartado de gastronomía?",
                options: ["Cachopo", "Lentejas", "Tortos con picadillo", "Fabada", "Pulpo a la gallega"],
                answer: 3
            },
            {
                question: "¿Qué restaurante se recomienda al visitar San Tirso de Abres?",
                options: ["Casa Fran", "Casa Carmela", "Restaurante Amaido", "El Solar", "El Regueranu"],
                answer: 2
            },
            {
                question: "¿Qué hito nos podemos encontrar al realizar la 'Ruta de los Arrieiros'?",
                options: ["Arroyo de Ramalledo", "Molino de Mazonovo", "Puente medieval de San Tirso", "Centro de la Pesca", "Casetos"],
                answer: 1
            },
            {
                question: "¿Qué elemento viene incluido en la 'Ruta del Ferrocarril en San Tirso de Abres'?",
                options: ["Bicicletas", "Pico minero", "Linterna", "Botella de agua", "Casco minero"],
                answer: 4
            },
            {
                question: "En el apartado de Meteorología, ¿cuántos días se pueden ver en la previsión?",
                options: ["Tres", "Cuatro", "Cinco", "Seis", "Siete"],
                answer: 4
            },
            {
                question: "¿Qué famoso postre es típico de San Tirso de Abres?",
                options: ["Casadielles", "Helado de vainilla", "Tarta de la abuela", "Turrón de Xixona", "Macedonia de frutas"],
                answer: 0
            }
        ];

        this.quizContainer = document.querySelectorAll('section')[0];
        this.resultContainer = document.querySelectorAll('section')[1];
        this.submitButton = document.querySelector('button');
    }

    buildQuiz() {
        this.questions.forEach((question, index) => {
            const questionArticle = document.createElement('article');
            questionArticle.innerHTML = `<h4>${index + 1}. ${question.question}</h4>`;
            
            question.options.forEach((option, optionIndex) => {
                const optionInput = document.createElement('input');
                optionInput.type = 'radio';
                optionInput.name = `question${index}`;
                optionInput.value = optionIndex;

                const optionLabel = document.createElement('label');
                optionLabel.textContent = option;

                optionLabel.insertBefore(optionInput, optionLabel.firstChild);

                const optionContainer = document.createElement('p');
                optionContainer.appendChild(optionLabel);

                questionArticle.appendChild(optionContainer);
            });
            this.quizContainer.appendChild(questionArticle);
        });
    }

    showResult() {
        const answerInputs = document.querySelectorAll('input[type="radio"]:checked');
        this.resultContainer.textContent = '';

        if (!this.resultContainer.querySelector('h3')) {
            const heading = document.createElement('h3');
            heading.textContent = 'Resultado del test';
            this.resultContainer.appendChild(heading);
        }

        if (answerInputs.length !== this.questions.length) {
            const warning = document.createElement('p');
            warning.textContent = 'Por favor, responde todas las preguntas.';
            this.resultContainer.appendChild(warning);
            return;
        }

        let score = 0;
        const feedbackParagraph = document.createElement('p');

        this.questions.forEach((question, index) => {
            const correctAnswerIndex = question.answer;
            const correctAnswer = question.options[correctAnswerIndex];
            const selectedAnswerIndex = parseInt(answerInputs[index].value);

            if (selectedAnswerIndex === correctAnswerIndex) {
                score++;
            }
            feedbackParagraph.textContent += `${index + 1}. ${correctAnswer}. `;
        });

        const scoreFeedback = document.createElement('p');
        scoreFeedback.textContent = `Tu puntuación es ${score} / ${this.questions.length}.`;
        this.resultContainer.appendChild(feedbackParagraph);
        this.resultContainer.appendChild(scoreFeedback);

        this.submitButton.remove();
    }
}