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
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfParamMeta = document.querySelector('meta[name="csrf-param"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
        const csrfParam = csrfParamMeta ? csrfParamMeta.getAttribute('content') : '';

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
    if (!parentSelector) return;
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

// Global Top Preloader (Virtualmin style)
window.addEventListener('beforeunload', function() {
    const preloader = document.getElementById('top-preloader');
    if (preloader) preloader.style.display = 'block';
});

window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        const preloader = document.getElementById('top-preloader');
        if (preloader) preloader.style.display = 'none';
    }
});

// Offline detection
function updateOnlineStatus() {
    const offlineOverlay = document.getElementById('offline-overlay');
    if (!offlineOverlay) return;
    
    if (navigator.onLine) {
        offlineOverlay.style.display = 'none';
    } else {
        offlineOverlay.style.display = 'flex';
    }
}
window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
// Check on load in case it started offline
document.addEventListener('DOMContentLoaded', updateOnlineStatus);

// Global Preloader and Submit Button Disabler
document.addEventListener('DOMContentLoaded', function() {
    // Function to disable button and show spinner
    function disableSubmitButton(btn) {
        if (!btn || btn.disabled) return;
        
        // Show top preloader
        const preloader = document.getElementById('top-preloader');
        if (preloader) preloader.style.display = 'block';

        // Instant visual feedback: replace or prepend <i> icon
        if (btn.tagName === 'BUTTON') {
            const icon = btn.querySelector('i, svg');
            if (icon) {
                const spinner = document.createElement('i');
                spinner.className = 'loading loading-spinner loading-sm mr-2';
                icon.replaceWith(spinner);
            } else {
                const spinner = document.createElement('i');
                spinner.className = 'loading loading-spinner loading-sm mr-2';
                btn.prepend(spinner);
            }
        } else {
            btn.value = 'Procesando...';
        }

        // Disable button in the next event loop tick to prevent blocking standard submissions
        setTimeout(() => {
            btn.disabled = true;
            btn.classList.add('btn-disabled', 'opacity-80', 'cursor-not-allowed');
        }, 0);
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

// Automatic Tickets Badge Update
document.addEventListener('DOMContentLoaded', function() {
    const badge = document.getElementById('ticket-badge-count');
    if (!badge) return;

    function updateTicketBadge() {
        fetch('/tickets/badge-count')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data && typeof data.count !== 'undefined') {
                    const count = parseInt(data.count, 10);
                    badge.textContent = count;
                    if (count > 0) {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }

                    // PWA Dock Badge (macOS / supported browsers)
                    if ('setAppBadge' in navigator) {
                        if (count > 0) {
                            navigator.setAppBadge(count).catch(() => {});
                        } else {
                            navigator.clearAppBadge().catch(() => {});
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching ticket badge count:', error);
            });
    }

    // Expose globally so other actions can trigger an immediate update
    window.updateTicketBadge = updateTicketBadge;

    // Update every 60 seconds (60000 ms)
    setInterval(updateTicketBadge, 60000);
});