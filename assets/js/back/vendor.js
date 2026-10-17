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

/**
 * Public Sans, la police du gabarit de référence (voir .claude/charte-back,
 * décision n°3). Auto-hébergée par @fontsource : le projet a un plancher
 * Lighthouse à tenir, et une requête tierce bloquante y coûte cher.
 * Quatre graisses, 300 à 600, celles qu'emploie la charte.
 */
import '@fontsource/public-sans/300.css';
import '@fontsource/public-sans/400.css';
import '@fontsource/public-sans/500.css';
import '@fontsource/public-sans/600.css';

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

    const usualCategories = document.querySelectorAll('[data-usual-sub-categories]');
    if (usualCategories.length > 0) {
        import('./usual-category').then(({default: UsualCategory}) => {
            new UsualCategory(usualCategories);
        }).catch(error => console.error(error.message));
    }

    const scheduleFields = document.querySelectorAll('[data-schedule-target]');
    if (scheduleFields.length > 0) {
        import('./schedule-fields').then(({default: ScheduleFields}) => {
            new ScheduleFields(scheduleFields);
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

    /*
     * Une case qui ecrit une operation ne peut pas se contenter d'un appel en
     * arriere-plan : la ligne disparait de la liste et le solde change. Elle
     * envoie donc son formulaire, et la page revient a jour.
     */
    document.querySelectorAll('.checkbox-submit').forEach(el => {
        el.addEventListener('change', () => {
            const form = el.closest('form');
            if (form) {
                el.disabled = true;
                form.submit();
            }
        });
    });

    /**
     * Le statut passe dans le corps, pas dans la chaîne de requête : il est
     * écrit en base, et le jeton CSRF du formulaire part avec lui.
     */
    document.querySelectorAll('.checkbox-ajax').forEach(el => {
        el.addEventListener('change', () => {
            const form = el.closest('form');
            const body = new FormData(form);
            body.set('status', el.checked ? '1' : '0');
            const xHttp = new XMLHttpRequest();
            xHttp.open('POST', form.getAttribute('action'), true);

            /*
             * Le serveur refuse un jeton périmé par un 403. Sans ce retour, la
             * case restait cochée à l'écran alors que rien n'avait été écrit.
             * L'état visuel revient en arrière et la zone d'annonce le dit, pour
             * que le refus ne passe pas que par la couleur.
             */
            const status = document.querySelector('#ajax-status');
            xHttp.onload = () => {
                if (200 === xHttp.status) {
                    if (status) {
                        status.textContent = el.checked ? 'Opération pointée.' : 'Pointage retiré.';
                    }
                    return;
                }
                el.checked = !el.checked;
                if (status) {
                    status.textContent = "Le pointage n'a pas été enregistré. Rechargez la page et réessayez.";
                }
            };
            xHttp.onerror = () => {
                el.checked = !el.checked;
                if (status) {
                    status.textContent = "Le pointage n'a pas été enregistré. Vérifiez votre connexion.";
                }
            };

            xHttp.send(body);
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
