/** Import CSS */
import '../../scss/back/vendor.scss';

/** Import JS */

document.addEventListener('DOMContentLoaded', function () {

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

    const selects = document.querySelectorAll('select');
    if (selects.length > 0) {
        import('./choice').then(({default: Choice}) => {
            new Choice(selects);
        }).catch(error => console.error(error.message));
    }

});