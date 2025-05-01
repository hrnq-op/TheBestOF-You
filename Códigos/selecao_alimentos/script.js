document.addEventListener("DOMContentLoaded", function () {
    const elements = document.querySelectorAll('.animated-select');
    elements.forEach((el, index) => {
        setTimeout(() => {
            el.classList.add('show');
        }, index * 200); // Ajuste o tempo como quiser
    });
});
