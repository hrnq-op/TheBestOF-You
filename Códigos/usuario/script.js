document.addEventListener("DOMContentLoaded", function() {
    const selects = document.querySelectorAll('.animated-select');

    selects.forEach((select, index) => {
        setTimeout(() => {
            select.classList.add('show'); // Adiciona a classe 'show' após um atraso
        }, index * 300); // Atraso em milissegundos (300ms entre cada select)
    });
});