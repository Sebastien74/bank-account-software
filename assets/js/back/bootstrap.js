/**
 * Module : Back Bootstrap
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

export default function () {

    import('./bootstrap/tooltip').then(({default: Tooltip}) => {

        const initOne = (el) => {

            if (el.classList.contains('tooltip-loaded')) return;

            const instance = Tooltip.getOrCreateInstance(el, {
                container: 'body',
                trigger: 'hover focus',
                boundary: 'viewport',
                delay: {show: 80, hide: 40}
            });

            el.addEventListener('shown.bs.tooltip', () => {
                const variant = el.getAttribute('data-tooltip-variant');
                if (!variant) return;
                const tip = instance.tip;
                if (!tip) return;
                tip.setAttribute('data-variant', variant);
            });

            el.addEventListener('click', (event) => {
                event.stopPropagation();
                instance.update();
                instance.hide();
            });

            el.classList.add('tooltip-loaded');
        };

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(initOne);

    }).catch(error => console.error(error.message));

    import('./bootstrap/modal').then(({default: Modal}) => {
        document.querySelectorAll('.modal').forEach(modal => {
            if (!modal.classList.contains('modal-loaded')) {
                let bsModal = new Modal(modal);
                modal.classList.add('modal-loaded');
                document.querySelectorAll('.btn-form-errors').forEach(btn => {
                    btn.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true}));
                });
            }
        });
    }).catch(error => console.error(error.message));

    import('./bootstrap/toast').then(({default: Toast}) => {
        document.querySelectorAll('.toast').forEach(toast => {
            if (!toast.classList.contains('toast-loaded')) {
                let bsToast = new Toast(toast);
                /*
                 * Le delai suit le temps de lecture plutot qu'une constante :
                 * cinq secondes effacaient un compte rendu d'import avant
                 * qu'on l'ait fini. Quatre secondes pour le remarquer, plus
                 * cinquante millisecondes par signe - environ 200 mots par
                 * minute -, borne entre 8 et 20 secondes. Un refus ne part
                 * pas seul : son cout a le rater est plus eleve.
                 */
                if (toast.classList.contains('always-show') || 'alert' === toast.getAttribute('role')) {
                    return;
                }

                const length = (toast.textContent || '').trim().length;
                const delay = Math.min(20000, Math.max(8000, 4000 + length * 50));
                let timer = setTimeout(() => toast.remove(), delay);

                /* Le minuteur se suspend tant que le pointeur ou le focus est
                   sur le toast : sinon il disparait sous la main qui le lit. */
                const pause = () => clearTimeout(timer);
                const resume = () => {
                    timer = setTimeout(() => toast.remove(), delay);
                };
                toast.addEventListener('mouseenter', pause);
                toast.addEventListener('focusin', pause);
                toast.addEventListener('mouseleave', resume);
                toast.addEventListener('focusout', resume);
            }
        });
    }).catch(error => console.error(error.message));
}
