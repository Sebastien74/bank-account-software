/**
 * Module : Back Datepicker
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 * Doc : https://mcdatepicker.netlify.app/docs
 */

import MCDatepicker from 'mc-datepicker';
import moment from "moment";

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
