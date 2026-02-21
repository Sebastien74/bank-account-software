/**
 * Module : Vendor Lazy load background
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

import {isInViewport} from "../functions";

export default function (styles) {

    const setBackgrounds = function (backgrounds) {

        const init = function (backgrounds, preload = false) {

            const height = window.innerHeight;
            const width = window.innerWidth;
            const orientation = height > width ? 'portrait' : 'landscape';
            const screenType = width > 991 ? 'desktop' : (width < 768 ? 'mobile' : 'tablet');

            backgrounds.forEach(function (el) {

                if (isInViewport(el, 100)) {

                    let background = el.dataset.background;
                    const desktopBackground = el.dataset.desktopBackground;
                    const tabletBackground = el.dataset.tabletBackground;
                    const mobileBackground = el.dataset.mobileBackground;
                    const onlySmallScreen = el.classList.contains('bg-only-small');

                    if (orientation === 'portrait') {
                        if (screenType === 'mobile' && typeof mobileBackground !== 'undefined') {
                            background = mobileBackground;
                        } else if (screenType === 'mobile' && typeof tabletBackground !== 'undefined') {
                            background = tabletBackground;
                        } else if (screenType === 'tablet' && typeof tabletBackground !== 'undefined') {
                            background = tabletBackground;
                        } else if (screenType === 'tablet' && typeof mobileBackground !== 'undefined') {
                            background = mobileBackground;
                        }
                    }

                    background = orientation === 'landscape' && typeof desktopBackground !== 'undefined' ? desktopBackground : background;
                    if (onlySmallScreen && screenType === 'desktop') {
                        return;
                    }

                    // Apply background style
                    el.style.cssText = background;

                    // Preload if not already handled
                    if (preload && !el.dataset.preloadInserted && typeof background !== 'undefined' && background.includes('url(')) {
                        const urlMatch = background.match(/url\(["']?(.*?)["']?\)/);
                        if (urlMatch && urlMatch[1]) {
                            const imageUrl = urlMatch[1];
                            // Inject preload <link> into <head> if not already present
                            if (!document.querySelector(`link[rel="preload"][href="${imageUrl}"]`)) {
                                const preloadLink = document.createElement('link');
                                preloadLink.rel = 'preload';
                                preloadLink.as = 'image';
                                preloadLink.href = imageUrl;
                                preloadLink.fetchPriority = 'high';
                                document.head.appendChild(preloadLink);
                            }
                            el.dataset.preloadInserted = 'true';
                        }
                    }
                }
            });
        };

        init(backgrounds, true);
        window.addEventListener("resize", function () {
            init(backgrounds);
        });
        window.addEventListener("scroll", function () {
            init(backgrounds);
        });
    };

    styles.forEach(function (tag) {
        const styleDecode = JSON.parse(tag.dataset.style);
        styleDecode.forEach(function (style) {
            if (style.screen === 'desktop') {
                tag.dataset.background = style.style;
            } else if (style.screen === 'tablet') {
                tag.dataset.tabletBackground = style.style;
            } else if (style.screen === 'mobile') {
                tag.dataset.mobileBackground = style.style;
            }
        });
    });

    if (styles.length > 0) {
        setBackgrounds(styles);
    }
}
