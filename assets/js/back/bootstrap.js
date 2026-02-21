/**
 * Module : Back Bootstrap
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

import(/* webpackPreload: true */ './bootstrap/tooltip').then(({default: Tooltip}) => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltip => {
        if (!tooltip.classList.contains('tooltip-loaded')) {
            let bsTooltip = new Tooltip(tooltip);
            tooltip.addEventListener('click', event => {
                bsTooltip.update();
                bsTooltip.hide();
            });
            tooltip.classList.add('tooltip-loaded');
        }
    });
}).catch(error => console.error(error.message));

import(/* webpackPreload: true */ './bootstrap/modal').then(({default: Modal}) => {
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

import(/* webpackPreload: true */ './bootstrap/collapse').then(({default: Collapse}) => {
    document.querySelectorAll('.accordion').forEach(accordion => {
        if (!accordion.classList.contains('accordion-loaded')) {
            accordion.querySelectorAll('.accordion-collapse').forEach(collapse => {
                new Collapse(collapse, {
                    toggle: false
                });
            });
            accordion.classList.add('accordion-loaded');
        }
    });
}).catch(error => console.error(error.message));

import(/* webpackPreload: true */ './bootstrap/toast').then(({default: Toast}) => {
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
