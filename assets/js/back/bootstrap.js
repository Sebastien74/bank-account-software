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
                const toastElList = document.querySelectorAll('.toast');
                toastElList.forEach(function (el) {
                    if (!el.classList.contains('always-show')) {
                        setTimeout(function () {
                            el.remove();
                        }, 5000);
                    }
                });
            }
        });
    }).catch(error => console.error(error.message));
}
