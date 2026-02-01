document.addEventListener('click', function(e) {
    // Buscamos si el elemento clickeado (o su padre) tiene data-method
    const link = e.target.closest('a[data-method]');
    
    if (link) {
        e.preventDefault();
        
        // Confirmación (data-confirm)
        const message = link.getAttribute('data-confirm');
        if (message && !confirm(message)) {
            return;
        }

        const method = link.getAttribute('data-method').toUpperCase();
        const action = link.getAttribute('href');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfParam = document.querySelector('meta[name="csrf-param"]').getAttribute('content');

        // Creamos un formulario invisible y lo enviamos
        const form = document.createElement('form');
        form.method = method === 'POST' ? 'POST' : 'GET'; // Soporte básico
        form.action = action;
        
        // Input CSRF
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = csrfParam;
        hiddenField.value = csrfToken;
        
        form.appendChild(hiddenField);
        document.body.appendChild(form);
        form.submit();
    }
});

function Marquee(selector, speed) {
    const parentSelector = document.querySelector(selector);
    const clone = parentSelector.innerHTML;
    const firstElement = parentSelector.children[0];
    let i = 0;
    //let marqueeInterval: any;

    parentSelector.insertAdjacentHTML('beforeend', clone);
    parentSelector.insertAdjacentHTML('beforeend', clone);

    function startMarquee() {
        setInterval(function () {
            firstElement.style.marginLeft = `-${i}px`;
            if (i > firstElement.clientWidth) {
                i = 0;
            }
            i = i + speed;
        }, 0);
    }

    /*
    function stopMarquee() {
        clearInterval(marqueeInterval);
    }
    */

    //parentSelector.addEventListener('mouseenter', stopMarquee);
    //parentSelector.addEventListener('mouseleave', startMarquee);

    startMarquee();
}

window.addEventListener('load', () => Marquee('.marquee-animation', 0.7));