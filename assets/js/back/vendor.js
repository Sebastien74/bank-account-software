/**
 * Module : Back Vendor
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

import {lazyLoadComponent} from '../vendor/functions';
import bootstrap from './bootstrap';
import displayLoader from './display-loader';

/** Stimulus : Turbo et Chart.js sont enregistrés via assets/controllers.json */
import '../../bootstrap';

/** Import CSS */
import '../../scss/back/vendor.scss';

lazyLoadComponent('.collapse', () => import('./collapse'), (Collapse, els) => new Collapse(els));
lazyLoadComponent('#index-filters-form', () => import('./form-filters'), (Filters, el) => new Filters(el));

/**
 * Turbo remplace le corps du document sans recharger la page : DOMContentLoaded
 * n'est émis qu'au tout premier affichage. Toute initialisation doit donc être
 * rejouée sur turbo:load, sans quoi les composants meurent à la seconde navigation.
 */
const onPageReady = (callback) => {
    document.addEventListener('DOMContentLoaded', callback);
    document.addEventListener('turbo:load', callback);
};

onPageReady(function () {

    const body = document.body;

    const togglerNav = document.querySelector('button.nav-toggler-icon-wrap');
    if (togglerNav) {
        togglerNav.onclick = function (e) {
            e.preventDefault();
            togglerNav.querySelector('.nav-toggler-icon').classList.toggle('open');
            body.classList.toggle('menu-open');
        }
    }

    bootstrap();
    displayLoader();

    const selects = document.querySelectorAll('select');
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

    const passwordFields = document.querySelectorAll('.show-password');
    if (passwordFields.length > 0) {
        import('../vendor/components/password-field').then(({default: PasswordFields}) => {
            new PasswordFields(passwordFields);
        }).catch(error => console.error(error.message));
    }

    const btnGroupToggle = document.querySelector('.btn-group-toggle');
    if (btnGroupToggle) {
        import('./btn-group-toggle').then(({default: BtnToggles}) => {
            new BtnToggles();
        }).catch(error => console.error(error.message));
    }

    const tagsInput = document.querySelector('[data-role="tags-input"]');
    if (tagsInput) {
        import('./bootstrap/tags-input').then(({default: TagInputs}) => {
            new TagInputs();
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

    const oppositeBtn = document.querySelector('#open-btn-opposite');
    if (oppositeBtn) {
        oppositeBtn.onclick = function () {
            const sidebar = document.querySelector('#sidebar-opposite-menu');
            sidebar.classList.toggle('open');
        }
    }

    const phpinfo = document.getElementById('phpinfo-container');
    if (phpinfo) {
        import('./phpinfo').then(({default: Phpinfo}) => {
            new Phpinfo();
        }).catch(error => console.error(error.message));
    }
});

onPageReady(function () {

    const showEl = document.querySelector('#show-entity');
    if (!showEl) {
        return;
    }

    showEl.querySelectorAll('td.value').forEach(td => {
        const text = (td.textContent || '')
            .replace(/\u00A0/g, ' ') // Convert &nbsp; to regular space
            .trim();
        // HTML check: at least one real element inside (ignores text nodes)
        const hasElement = td.querySelector('*') !== null;
        // If no meaningful text AND no HTML element => remove
        if (!text && !hasElement) {
            td.closest('tr').remove();
        }
    });
});

onPageReady(function () {
    import('../vendor/components/lazy-load').then(({default: lazyLoad}) => {
        new lazyLoad();
    }).catch(error => console.error(error.message));
});
