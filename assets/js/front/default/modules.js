/**
 * Modules
 *
 * @copyright 2024
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */

/** Stimulus */
// import './stimulus';
import {lazyLoadComponent, scrollToEL, RemoveAttrsTitle} from "./functions";

document.addEventListener('DOMContentLoaded', function () {

    lazyLoadComponent('#main-preloader', () => import(/* webpackPreload: true */'./components/preloader'), (Preloader) => new Preloader());
    lazyLoadComponent('.media-block', () => import(/* webpackPreload: true */'./components/medias'), (Medias, els) => new Medias(els));
    lazyLoadComponent('.splide', () => import(/* webpackPreload: true */'./components/splide-slider'), (Sliders, els) => new Sliders(els));
    lazyLoadComponent('.marquee', () => import(/* webpackPreload: true */'./components/marquee'), (Marquees, els) => new Marquees(els));
    lazyLoadComponent('.zones-navigation', () => import(/* webpackPreload: true */'./components/zones-navigation'), (Navigations, els) => new Navigations(els));
    lazyLoadComponent('.glightbox', () => import(/* webpackPreload: true */'../../vendor/plugins/popup'), (Popups) => new Popups());
    lazyLoadComponent('[data-component="masonry"]', () => import(/* webpackPreload: true */'./components/masonry'), (Masonry, els) => new Masonry(els));
    lazyLoadComponent('.social-wall-wrap', () => import(/* webpackPreload: true */'./components/social-wall'), (socialWalls, els) => new socialWalls(els));
    lazyLoadComponent('[data-component="counter"]', () => import(/* webpackPreload: true */'./components/counters'), (Counters, els) => new Counters(els));
    lazyLoadComponent('.parallax', () => import(/* webpackPreload: true */'./components/parallax'), (Parallax, els) => new Parallax(els));
    lazyLoadComponent('.share-content', () => import(/* webpackPreload: true */'./components/share'), (ShareBoxes) => new ShareBoxes());
    lazyLoadComponent('#website-alert', () => import(/* webpackPreload: true */'./components/website-alert'), (Alerts) => new Alerts());
    lazyLoadComponent('font', () => import(/* webpackPreload: true */'./components/fonts'), (Fonts) => new Fonts());
    lazyLoadComponent('#webmaster-box', () => import(/* webpackPreload: true */'../../vendor/components/webmaster'), (Webmaster, el) => new Webmaster(el));
    lazyLoadComponent('#scroll-top-btn', () => import(/* webpackPreload: true */'./components/scroll'), (Scroll) => new Scroll());
    lazyLoadComponent('.scroll-link', () => import(/* webpackPreload: true */'./components/scroll'), (Scroll) => new Scroll());
    lazyLoadComponent('.newsletter-form-container', () => import(/* webpackPreload: true */'./components/form/newsletter'), (Newsletters) => new Newsletters());

    /** Scroll to el on click */
    document.querySelectorAll(".as-scroll-link").forEach(el => {
        el.onclick = function (e) {
            e.preventDefault();
            const scrollToEl = document.querySelector(el.getAttribute('href'));
            if (scrollToEl) {
                scrollToEL(scrollToEl, false);
            }
        }
    });

    RemoveAttrsTitle();

    /** To remove empty associated entities teaser */
    document.querySelectorAll('.empty-associated-entities').forEach(function (el) {
        const zone = el.closest('.layout-zone');
        if (zone) {
            zone.remove();
        }
    });

    // /** Highlight */
    // import hljs from 'highlight.js';
    // import '../../../../scss/front/default/components/highlight/theme.scss';
    // import javascript from 'highlight.js/lib/languages/javascript';
    // /** Then register the languages you need */
    // hljs.registerLanguage('javascript', javascript);
    // hljs.highlightAll();
});