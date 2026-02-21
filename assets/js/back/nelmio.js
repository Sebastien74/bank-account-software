/**
 * Module : Back Vendor
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

/** Import CSS */
import '../../scss/back/nelmio.scss';

import('../vendor/components/lazy-load').then(({default: lazyLoad}) => {
    new lazyLoad();
}).catch(error => console.error(error.message));

document.addEventListener('DOMContentLoaded', function () {

    const btnSizes = function () {
        document.querySelectorAll('.opblock-summary-method').forEach(el => {
            el.removeAttribute('style');
            el.style.height = el.parentElement.offsetHeight + 'px';
        });
    }

    window.addEventListener("resize", function () {
        btnSizes();
    });

    setTimeout(function () {
        btnSizes();
    }, 250);
});
