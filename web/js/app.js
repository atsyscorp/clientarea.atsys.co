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

// Global Preloader and Submit Button Disabler
document.addEventListener('DOMContentLoaded', function() {
    // Function to disable button and show spinner
    function disableSubmitButton(btn) {
        if (!btn || btn.disabled) return;
        btn.disabled = true;
        btn.classList.add('btn-disabled', 'opacity-80', 'cursor-not-allowed');
        // Do not add the 'loading' class to the button itself, as it applies DaisyUI's mask-image causing the button to stretch and distort.
        if (btn.tagName === 'BUTTON') {
            btn.innerHTML = '<span class="loading loading-spinner loading-sm mr-2"></span> Procesando...';
        } else {
            btn.value = 'Procesando...';
        }
    }

    // 1. Hook into jQuery for Yii 2 ActiveForms (only triggers after validation passes!)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('beforeSubmit', 'form', function() {
            const form = jQuery(this);
            if (form.attr('method')?.toLowerCase() === 'get') return;
            const btn = form.find('button[type="submit"], input[type="submit"]');
            if (btn.length) {
                disableSubmitButton(btn[0]);
            }
        });
    }

    // 2. Fallback for non-Yii or simple submit forms (e.g. Logout button, delete actions)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        // Skip search forms (GET) and forms already handled by ActiveForm to prevent double disabling
        if (form.getAttribute('method')?.toLowerCase() === 'get' || (typeof jQuery !== 'undefined' && jQuery(form).data('yiiActiveForm'))) {
            return;
        }
        const btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (btn) {
            disableSubmitButton(btn);
        }
    });
});