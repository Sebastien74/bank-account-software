/**
 * On loaded
 *
 * @copyright 2024
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */
document.addEventListener('DOMContentLoaded', function () {

    // Target all elements inside .body that have a style attribute
    document.querySelectorAll('.body [style]').forEach(el => {
        // Extract the inline style as individual declarations
        const declarations = el.getAttribute('style').split(';').filter(d => d.trim() !== '');
        // Reconstruct the style with !important
        const newStyle = declarations.map(decl => {
            const [prop, value] = decl.split(':');
            return `${prop.trim()}: ${value.trim()} !important`;
        }).join('; ');
        // Replace the style attribute with the modified version
        el.setAttribute('style', newStyle);
    });

    import(/* webpackPreload: true */ '../../vendor/components/log-errors').then(({default: Log}) => {
        new Log();
    }).catch(error => console.error(error.message));

    const body = document.body;

    document.querySelectorAll('link.preload-css[rel="preload"]').forEach(link => {
        link.rel = 'stylesheet';
    });

    document.querySelectorAll('.js-open-window').forEach(button => {
        button.addEventListener('click', () => {
            const url = button.getAttribute('data-url');
            if (url) {
                window.open(url, '_blank', 'noopener,noreferrer');
            }
        });
    });

    const zoomLevel = function () {
        let browserZoomLevel = Math.round(window.devicePixelRatio * 100);
        body.setAttribute('data-browser-zoom-level', browserZoomLevel.toString());
        body.classList.add('zoom-' + browserZoomLevel);
    }
    zoomLevel();

    window.addEventListener('resize', function () {
        zoomLevel();
    });

    import(/* webpackPreload: true */ '../../vendor/components/lazy-load').then(({default: lazyLoad}) => {
        new lazyLoad();
    }).catch(error => console.error(error.message));

    /** To set overflow to sticky parents elements */
    function getParentsUntilBody(element) {
        const parents = [];
        while (element.parentElement && element.parentElement.tagName !== 'BODY') {
            element = element.parentElement;
            parents.push(element);
        }
        if (element.parentElement && element.parentElement.tagName === 'BODY') {
            parents.push(document.body);
        }
        return parents;
    }

    const targetElement = document.querySelector('.col-sticky');
    if (targetElement) {
        const parents = getParentsUntilBody(targetElement);
        parents.forEach(parent => {
            parent.classList.add('overflow-initial');
        });
        body.classList.add('body-sticky-col');
    }
});