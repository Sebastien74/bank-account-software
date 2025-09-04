/**
 * Animations
 *
 * @copyright 2024
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */
document.addEventListener('DOMContentLoaded', function () {

    /** Animations */

    let animDown = document.querySelector('.down-vertical-parallax');
    let animUp = document.querySelector('.up-vertical-parallax');
    let animRight = document.querySelector('.right-horizontal-parallax');
    let animLeft = document.querySelector('.left-horizontal-parallax');
    if (animDown || animUp || animRight || animLeft) {
        import('./components/animation').then(({default: anim}) => {
            new anim();
        }).catch(error => console.error(error.message));
    }

    let aosEl = document.querySelector('*[data-aos]');
    if (aosEl) {
        import('./components/aos').then(({default: AOS}) => {
            new AOS();
        }).catch(error => console.error(error.message));
    }

    let animateEls = document.querySelectorAll('*[data-animation]')
    if (animateEls.length > 0) {
        import('./components/animate-css').then(({default: animate}) => {
            new animate(animateEls);
        }).catch(error => console.error(error.message));
    }
});