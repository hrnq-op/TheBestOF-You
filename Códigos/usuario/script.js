document.addEventListener("DOMContentLoaded", function() {
    const selects = document.querySelectorAll('.animated-select');

    selects.forEach((select, index) => {
        setTimeout(() => {
            select.classList.add('show'); // Faz aparecer o select com delay
        }, index * 300); // Atraso de 300ms entre cada select
    });
    
});