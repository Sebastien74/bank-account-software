/**
 * Module : Security Preloader
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

const body = document.body;
const preloader = document.getElementById("main-preloader");

if (preloader) {

    preloader.classList.add('d-none');

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            if (!preloader.classList.contains('d-none')) {
                preloader.classList.add('d-none');
            }
        }
    });

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener("submit", function (e) {
            preloader.classList.remove('d-none');
        });
    });
}