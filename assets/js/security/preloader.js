/**
 * Preloader
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

let body = document.body
let preloader = document.getElementById("main-preloader")

if (preloader) {

    preloader.classList.add('d-none')

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            if (!preloader.classList.contains('d-none')) {
                preloader.classList.add('d-none')
            }
        }
    })

    let preloaderEls = document.querySelectorAll('[data-toggle="preloader"]')
    for (let i = 0; i < preloaderEls.length; i++) {
        preloaderEls[i].addEventListener("click", function (e) {
            if (e.which !== 2) {
                preloader.classList.remove('d-none')
            }
        })
    }

    let forms = document.querySelectorAll('form')
    for (let i = 0; i < forms.length; i++) {
        forms[i].addEventListener("submit", function (e) {
            preloader.classList.remove('d-none')
        })
    }
}