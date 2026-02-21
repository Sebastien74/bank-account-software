/**
 * Module : Back Vendor
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

/** Import CSS */
import '../../scss/back/vendor.scss';

document.addEventListener('DOMContentLoaded', function () {

    const body = document.body;

    const togglerNav = document.querySelector('button.nav-toggler-icon-wrap');
    togglerNav.onclick = function (e) {
        e.preventDefault();
        togglerNav.querySelector('.nav-toggler-icon').classList.toggle('open');
        body.classList.toggle('menu-open');
    }

    import('./bootstrap');

    const selects = document.querySelectorAll('select:not(.initial)');
    if (selects.length > 0) {
        import('./choice').then(({default: Choice}) => {
            new Choice(selects);
        }).catch(error => console.error(error.message));
    }

    const datepickers = document.querySelectorAll('.js-datepicker');
    if (datepickers.length > 0) {
        import('./datepicker').then(({default: Datepicker}) => {
            new Datepicker(datepickers);
        }).catch(error => console.error(error.message));
    }

    document.querySelectorAll('.checkbox-ajax').forEach(el => {
        el.addEventListener('change', () => {
            const checked = el.checked ? '1' : '0';
            const form = el.closest('form');
            const xHttp = new XMLHttpRequest();
            xHttp.open('POST', form.getAttribute('action') + '?status=' + checked, true);
            xHttp.send(new FormData(form));
        });
    });
});
