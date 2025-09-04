// import './cart';
import './animation';
import './entities-filters';
import './form/form';
import './form/newsletter';
import './scroll';
import {isInViewport} from "../functions";

// import tables from './table'
// let tablesElements = document.getElementsByClassName('table-responsive')
// tables(tablesElements)

try {

    const parallaxElements = document.querySelectorAll('.parallax')
    if (parallaxElements.length > 0) {
        import(/* webpackPreload: true */ './parallax').then(({default: parallax}) => {
            new parallax(parallaxElements)
        }).catch(error => console.error(error.message));
    }

    let scroller = document.getElementById('scroll-wrapper')
    if (document.body.contains(scroller)) {
        import(/* webpackPreload: true */ './scroll-infinite').then(({default: scrollInfiniteModule}) => {
            new scrollInfiniteModule(scroller)
        }).catch(error => console.error(error.message));
    }

    let socialWalls = document.querySelectorAll('.social-wall-wrap')
    if (socialWalls.length > 0) {
        import(/* webpackPreload: true */ './social-wall').then(({default: socialWallsModule}) => {
            new socialWallsModule(socialWalls)
        }).catch(error => console.error(error.message));
    }

    // let boxAlertElem = document.getElementById('website-alert')
    // if (boxAlertElem) {
    //     import(/* webpackPreload: true */ './website-alert').then(({default: boxAlert}) => {
    //         new boxAlert(boxAlertElem)
    // }).catch(error => console.error(error.message));
    // }

    // let popupImages = document.getElementsByClassName('glightbox')
    // if (popupImages.length > 0) {
    //     import(/* webpackPreload: true */ '../../../vendor/plugins/popup').then(({default: popup}) => {
    //         new popup()
    // }).catch(error => console.error(error.message));
    // }

    // let splideSlider = document.getElementsByClassName('splide')
    // if (splideSlider.length > 0) {
    //     import(/* webpackPreload: true */ './splide-slider').then(({default: splide}) => {
    //         new splide(splideSlider)
    // }).catch(error => console.error(error.message));
    // }

    let maps = document.querySelectorAll('.map-box');
    if (maps.length > 0) {
        let mapModule = function () {
            import('./map/map').then(({default: mapModule}) => {
                new mapModule(maps);
                document.body.classList.add('map-initialized');
            }).catch(error => console.error(error.message));
        }
        if (isInViewport(maps[0])) {
            mapModule();
        } else {
            window.addEventListener('scroll', function (e) {
                mapModule();
            });
        }
    }

    let eventsMapEl = document.getElementById('map-container-events')
    if (eventsMapEl) {
        import('./map/event-map').then(({default: eventsMap}) => {
            new eventsMap(eventsMapEl)
        }).catch(error => console.error(error.message));
    }

    let grids = document.querySelectorAll('.masonry-wrap');
    if (grids.length > 0) {
        import(/* webpackPreload: true */ '../../../vendor/plugins/masonry').then(({default: masonry}) => {
            new masonry(grids)
        }).catch(error => console.error(error.message));
    }

    let calendars = document.getElementsByClassName('calendar-render-container')
    if (calendars.length > 0) {
        import(/* webpackPreload: true */ './calendar').then(({default: calendars}) => {
            new calendars()
        }).catch(error => console.error(error.message));
    }

    let countersEl = document.querySelectorAll('[data-component="counter"]')
    if (countersEl.length > 0) {
        import(/* webpackPreload: true */ './counters').then(({default: counters}) => {
            new counters(countersEl)
        }).catch(error => console.error(error.message));
    }

    let formCalendars = document.querySelectorAll('[data-component="form-calendar"]')
    if (formCalendars.length > 0) {
        import(/* webpackPreload: true */ './form/form-calendar').then(({default: formCalendar}) => {
            new formCalendar()
        }).catch(error => console.error(error.message));
    }

    let aosElements = document.querySelectorAll('*[data-aos]')
    if (aosElements.length > 0) {
        import(/* webpackPreload: true */ './aos').then(({default: AOS}) => {
            new AOS()
        }).catch(error => console.error(error.message));
    }

    let animateEls = document.querySelectorAll('*[data-animation]')
    if (animateEls.length > 0) {
        import(/* webpackPreload: true */ './animate-css').then(({default: animate}) => {
            new animate(animateEls)
        }).catch(error => console.error(error.message));
    }

    let formSearch = document.querySelector('.search-engine-form');
    if (formSearch.length > 0) {
        import(/* webpackPreload: true */ './search').then(({default: Search}) => {
            new Search()
        }).catch(error => console.error(error.message));
    }

} catch (error) {
    console.error(error)
}
