/**
 * Module : Vendor show password field
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

export default function (buttons) {
    buttons.forEach(btn => {
        btn.onclick = function () {
            btn.querySelector('.show-icon').classList.toggle('d-none');
            btn.querySelector('.hide-icon').classList.toggle('d-none');
            const input = btn.closest('.input-group').querySelector('.form-control');
            if (!input.classList.contains('show')) {
                input.classList.add('show')
                input.type = 'text'
            } else {
                input.classList.remove('show')
                input.type = 'password'
            }
        }
    });
}
