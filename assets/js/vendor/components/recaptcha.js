/**
 * Module : Vendor Recaptcha
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

export function generate() {

    const dataEl = document.getElementById('data-path');

    const recaptcha = function () {

        let body = document.body;

        let recaptchaEl = document.getElementById('recaptcha');
        if (recaptchaEl) {
            if (recaptchaEl.classList.contains('d-none')) {
                recaptchaEl.classList.remove('d-none');
            }
            recaptchaEl.onclick = function () {
                if (!recaptchaEl.classList.contains('active')) {
                    recaptchaEl.classList.add('active');
                }
            }
            recaptchaEl.addEventListener('mouseleave', e => {
                if (recaptchaEl.classList.contains('active')) {
                    recaptchaEl.classList.remove('active');
                }
            })
        }

        body.querySelectorAll('form.security').forEach(function (form) {
            const data = form.querySelector('.form-data');
            const string = encodeURIComponent(data.dataset.id);
            if (string !== '') {
                let xHttp = new XMLHttpRequest();
                xHttp.open("GET", dataEl.dataset.encrypt + '/' + string, true);
                xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
                xHttp.send();
                xHttp.onload = function (e) {
                    if (this.readyState === 4 && this.status === 200) {
                        let response = JSON.parse(this.response);
                        if (response.result !== false) {
                            let field = form.querySelector('.field_ho');
                            field.dataset.honey = response.result;
                        }
                    }
                }
            }
        });
    }

    recaptcha();
}

export function onSubmit(form) {
    let honeyField = form.querySelector('.field_ho');
    if (honeyField) {
        honeyField.style.position = 'initial';
        honeyField.style.left = 'initial';
        honeyField.type = 'hidden';
        if (!honeyField.value) {
            honeyField.value = honeyField.dataset.honey;
        }
    }
}
