import {isInViewport} from "../functions";

/**
 * Media loader
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let loaderRequest = function () {

        const body = document.body;
        const el = document.querySelector('.hx-include-in-viewport');

        if (el && !body.classList.contains('media-loader-active')) {
            body.classList.add('media-loader-active');
            let xHttp = new XMLHttpRequest();
            xHttp.open("GET", el.getAttribute('src'), true);
            xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
            xHttp.send();
            xHttp.onload = function () {
                if (this.readyState === 4 && this.status === 200) {
                    if (!el.classList.contains('only-hx')) {
                        let response = JSON.parse(this.response);
                        let loaderWrap = el.closest('.img-loader-wrap');
                        let loader = loaderWrap.querySelector('.img-loader');
                        loaderWrap.innerHTML = response.html;
                        if (loader) {
                            loader.remove();
                        }
                    } else {
                        el.remove();
                    }
                    body.classList.remove('media-loader-active');
                    loaderRequest();
                }
            };
        }
    }

    let inViewport = function (offset = 0) {
        document.querySelectorAll('hx\\:include').forEach(function (el) {
            if (isInViewport(el, offset)) {
                el.classList.add('hx-include-in-viewport')
            }
        });
        loaderRequest();
    }

    inViewport();
    window.onscroll = function () {
        inViewport(300);
    }
};
