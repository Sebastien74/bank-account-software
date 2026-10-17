/**
 * Module : Back Datepicker
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 * Doc : https://mcdatepicker.netlify.app/docs
 */

import MCDatepicker from 'mc-datepicker';
import moment from "moment";

/*
 * MCDatepicker n'ouvre qu'un seul calendrier, accroche a <body> et garde en
 * memoire de module. Turbo remplace <body> a chaque navigation : le noeud
 * part avec l'ancien corps, mais la bibliotheque continue de le croire pose
 * et refuse d'en reconstruire un. Les champs de date rendus apres un
 * enregistrement n'ouvraient donc plus rien.
 *
 * Les instances sont suivies ici pour etre detruites pendant que l'ancien
 * corps est encore en place -- `remove()` decroche le calendrier de son
 * parent, et le faire trop tard leverait sur un noeud deja detache. La
 * bibliotheque remet alors sa reference a zero et le prochain passage
 * reconstruit le calendrier.
 */
let instances = [];

const teardown = () => {
    const current = instances;
    instances = [];
    current.forEach(instance => {
        try {
            instance.destroy();
        } catch (error) {
            console.error(error.message);
        }
    });
};

/*
 * `turbo:before-cache` precede l'instantane : le calendrier en est retire,
 * sans quoi un retour arriere en afficherait une copie morte. Une visite non
 * mise en cache ne l'emet pas, d'ou le second point d'accroche, qui precede
 * lui aussi l'echange du corps.
 */
document.addEventListener('turbo:before-cache', teardown);
document.addEventListener('turbo:before-render', teardown);

export default function (pickers) {

    const trans = document.getElementById('data-translation');
    const localeSplit = document.documentElement.getAttribute('lang').split('_');
    const locale = localeSplit.shift();

    moment.locale(locale);

    pickers.forEach(picker => {

        const id = picker.getAttribute('id');
        const minDate = typeof picker.dataset.min !== 'undefined' ? new Date(picker.dataset.min) : false;
        const maxDate = typeof picker.dataset.max !== 'undefined' ? new Date(picker.dataset.max) : false;

        let options = {
            el: '#' + id,
            bodyType: 'modal',
            customWeekDays: moment.weekdays(),
            customMonths: moment.months(),
            customOkBTN: trans.dataset.ok,
            customCancelBTN: trans.dataset.close,
            customClearBTN: trans.dataset.deselect,
            dateFormat: trans.dataset.formatDate,
            autoClose: true,
            closeOnBlur: true,
            theme: {
                theme_color: '#6f42c1'
            },
            ...(minDate && {minDate}),
            ...(maxDate && {maxDate}),
        }

        const datePicker = MCDatepicker.create(options);
        instances.push(datePicker);

        datePicker.onOpen(() => {
            const minYear = minDate ? minDate.getFullYear() : null;
            const maxYear = maxDate ? maxDate.getFullYear() : null;
            if (minYear && maxYear && minYear === maxYear) {
                const waitForPicker = () => {
                    const mcContainer = document.querySelector('.mc-calendar--modal');
                    if (mcContainer) {
                        const yearSelector = mcContainer.querySelector('.mc-select__year');
                        if (yearSelector) {
                            yearSelector.style.display = 'none';
                        }
                    } else {
                        requestAnimationFrame(waitForPicker);
                    }
                };
                waitForPicker();
            }
        });
    });
}
