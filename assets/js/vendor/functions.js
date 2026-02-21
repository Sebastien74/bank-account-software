/**
 * Module : Vendor Functions
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

/**
 * To load modules.
 */
export function lazyLoadComponent(selector, importFn, init) {
    const asId = selector.includes('#');
    const els = asId ? document.querySelector(selector) : document.querySelectorAll(selector);
    const haveEls = (asId && els) || (!asId && els.length > 0);
    haveEls && importFn().then(m => init(m.default, els)).catch(e => console.error(e.message));
}

/**
 * Create a one-time dynamic module loader.
 */
export function createModuleLoader(importFn) {
    let modulePromise = null;
    return () => {
        if (modulePromise) return modulePromise;
        modulePromise = importFn().then(m => m?.default ?? m);
        return modulePromise;
    };
}

/**
 * Create a one-time CSS loader from a dynamic import of SCSS/CSS.
 */
export function createCssLoader(importCssFn, opts = {}) {
    let cssPromise = null;
    const {bodyClass} = opts;
    return () => {
        if (cssPromise) return cssPromise;
        if (bodyClass) document.body.classList.add(bodyClass);
        cssPromise = importCssFn();
        return cssPromise;
    };
}

/**
 * Breakpoints.
 */
export function breakpoints() {
    return {
        xs: 320,
        sm: 576,
        md: 768,
        lg: 992,
        xl: 1200,
        xxl: 1400,
        xxxl: 1600
    };
}

/**
 * Breakpoint.
 */
export function breakpointSize(limit) {
    return breakpoints()[limit];
}

/**
 * To set tab panes height.
 */
export function tabPanesHeight(tabEl) {
    let tabHeight = 0;
    tabEl.querySelectorAll('.tab-pane').forEach(function (paneEl) {
        if (paneEl.clientHeight > tabHeight) {
            tabHeight = paneEl.clientHeight;
        }
    });
    if (tabHeight > 0) {
        tabEl.querySelectorAll('.tab-pane').forEach(function (paneEl) {
            paneEl.style.height = tabHeight + 'px';
        });
    }
}

/** Check if element is visible */
export function isNode(el) {
    return !!el && !!el.nodeType;
}

/** Check if element is visible */
export function isElement(el) {
    return v => v?.nodeType === Node.ELEMENT_NODE;
}

const toElements = (input) => {
    if (typeof input === 'string') return [...document.querySelectorAll(input)];
    if (isElement(input)) return [input];
    if (input instanceof NodeList || Array.isArray(input))
        return [...input].filter(isElement);
    return [];
};

const isCssVisible = (el) => {
    if (!el || !el.isConnected) return false;
    if (el.getClientRects().length === 0) return false;
    for (let n = el; n; n = n.parentElement) {
        if (n.hasAttribute?.('hidden')) return false;
        const cs = getComputedStyle(n);
        if (cs.display === 'none' || cs.visibility === 'hidden' || cs.contentVisibility === 'hidden') {
            return false;
        }
    }
    return true;
};

/** Check if element is CSS-visible (not display:none/hidden/etc.) */
export function isVisible(input) {
    return toElements(input).some(isCssVisible);
}

/**
 * To check if element is in viewport.
 */
export function isInViewport(el, offset = 0) {
    const bounding = el.getBoundingClientRect(),
        myElementHeight = el.offsetHeight,
        myElementWidth = el.offsetWidth;
    return bounding.top >= -myElementHeight
        && bounding.left >= -myElementWidth
        && bounding.right <= (window.innerWidth + offset || document.documentElement.clientWidth + offset) + myElementWidth
        && bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight) + myElementHeight;
}

/**
 * To scroll to an element.
 */
export function scrollToEL(el, middle = true, offset = 0) {
    let mainMenu = document.getElementById('main-navigation');
    let offsetTop = mainMenu && (mainMenu.classList.contains('sticky-top') || mainMenu.classList.contains('as-scroll')) ? mainMenu.getBoundingClientRect().height * 1.5 : 0;
    let elOffset = el.getBoundingClientRect().top + window.scrollY;
    let elHeight = el.offsetHeight;
    let windowHeight = window.innerHeight;
    if (elHeight < windowHeight && middle) {
        offset = elOffset - ((windowHeight / 2) - (elHeight / 2));
    } else {
        offset = elOffset;
    }
    offset = offsetTop > 0 ? offset - offsetTop : elOffset;
    window.scrollTo({top: offset, behavior: 'smooth'});
}

/**
 * Anchors events.
 */
export function AnchorsEvents() {

    const body = document.body;

    const events = function () {

        /** Anchors offset of element */
        let mainNavigation = document.getElementById('main-navbar');

        let scrollToAnchor = function (elToScroll) {
            let elOffset = elToScroll.getBoundingClientRect().top + window.scrollY;
            let navbarContainer = mainNavigation.closest('.menu-container') ? mainNavigation.closest('.menu-container') : mainNavigation;
            let navbarHeight = navbarContainer.classList.contains('sticky-top') || navbarContainer.classList.contains('fixed-top') ? mainNavigation.clientHeight : 0;
            let offset = elOffset - navbarHeight;
            window.scrollTo({top: offset, behavior: 'smooth'});
            if (!body.classList.contains('scroll-fix')) {
                setTimeout(function () {
                    scrollToAnchor(elToScroll);
                    body.classList.add('scroll-fix');
                }, 500);
            }
        }

        /** Anchors links on page loaded */
        document.addEventListener('DOMContentLoaded', function () {
            let queryHash = window.location.hash;
            if (queryHash && !queryHash.includes('#/')) {
                let elToScroll = document.querySelector(queryHash);
                if (elToScroll) {
                    scrollToAnchor(elToScroll);
                }
            }
        }, false);

        /** Anchors links on click */
        const burgerBtn = document.querySelector('.nav-toggler-icon');
        const anchors = document.querySelectorAll('.as-anchor');
        anchors.forEach(function (anchor) {
            let elToScroll = document.querySelector(anchor.dataset.anchor);
            if (elToScroll) {
                anchor.onclick = function (event) {
                    if (mainNavigation) {
                        mainNavigation.classList.add('force-as-scroll');
                    }
                    event.preventDefault();
                    anchors.forEach(function (el) {
                        el.classList.remove('active');
                    });
                    anchor.classList.add('active');
                    scrollToAnchor(elToScroll);
                    if (burgerBtn.classList.contains('open')) {
                        burgerBtn.click();
                    }
                }
            }
        });
    }

    events();
    window.addEventListener('resize', function () {
        events();
    });
}

/**
 * Attach a swipe-left listener on a given element.
 *
 * @param {HTMLElement} element - Target element to listen on
 * @param {Function} callback - Function called when a left swipe is detected
 */
export function onSwipeLeft(element, callback) {

    let startX = 0;
    let startY = 0;

    const MIN_HORIZONTAL_DISTANCE = 50; // minimum px to consider it a swipe
    const MAX_VERTICAL_DISTANCE = 80;   // max vertical deviation allowed

    /**
     * Handle touchstart event.
     * @param {TouchEvent} event
     */
    function handleTouchStart(event) {
        const touch = event.changedTouches[0];
        startX = touch.clientX;
        startY = touch.clientY;
    }

    /**
     * Handle touchend event.
     * @param {TouchEvent} event
     */
    function handleTouchEnd(event) {
        const touch = event.changedTouches[0];
        const endX = touch.clientX;
        const endY = touch.clientY;

        const deltaX = endX - startX; // < 0 if going left
        const deltaY = endY - startY;

        const absDeltaX = Math.abs(deltaX);
        const absDeltaY = Math.abs(deltaY);

        // Check horizontal swipe, mainly horizontal, and direction = left
        if (
            absDeltaX > MIN_HORIZONTAL_DISTANCE &&
            absDeltaY < MAX_VERTICAL_DISTANCE &&
            deltaX < 0
        ) {
            callback();
        }
    }

    element.addEventListener('touchstart', handleTouchStart, {passive: true});
    element.addEventListener('touchend', handleTouchEnd, {passive: true});
}
