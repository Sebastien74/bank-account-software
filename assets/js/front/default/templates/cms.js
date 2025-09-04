/** Import CSS */
import '../../../../scss/front/default/templates/cms.scss';

/** Import JS */
import {isInViewport} from "../functions";
import "../components/remove-empty-blocks";

/** To add h-100 class if only card block in zone */
document.querySelectorAll('.layout-zone').forEach(function (zone) {
    const blocksLength = zone.querySelectorAll('.layout-block').length;
    const cards = zone.querySelectorAll('.card-block');
    const cardsLength = cards.length;
    const onlyCards = cardsLength === blocksLength;
    if (onlyCards) {
        cards.forEach(function (card) {
            card.classList.add('h-100', 'h-100-dynamic');
        });
    }
});

const form = document.querySelector('form');
if (form) {
    import(/* webpackPreload: true */ '../components/form/form').then(({default: Form}) => {
        new Form();
    }).catch(error => console.error(error.message));
}

/** Show more loading */
let showMoreIndexBtn = document.getElementById('show-more-index-btn');
if (showMoreIndexBtn) {
    import(/* webpackPreload: true */ '../components/show-more-index').then(({default: ShowMoreIndexModule}) => {
        new ShowMoreIndexModule(showMoreIndexBtn)
    }).catch(error => console.error(error.message));
}

/** Ajax pagination */
const pagination = document.querySelector('.pagination-ajax-wrap');
if (pagination) {
    import(/* webpackPreload: true */ '../components/ajax-pagination').then(({default: AjaxPagination}) => {
        new AjaxPagination()
    }).catch(error => console.error(error.message));
}

/** Scroll infinite */
let scroller = document.getElementById('scroll-wrapper')
if (document.body.contains(scroller)) {
    import(/* webpackPreload: true */ '../components/scroll-infinite').then(({default: ScrollInfiniteModule}) => {
        new ScrollInfiniteModule(scroller)
    }).catch(error => console.error(error.message));
}

/** Search form filters */
let searchFormsFilters = document.querySelectorAll('.entities-filters-form');
if (searchFormsFilters) {
    import(/* webpackPreload: true */ '../components/entities-filters').then(({default: searchForm}) => {
        new searchForm(searchFormsFilters);
    }).catch(error => console.error(error.message));
}

/** Catalog filter */
let indexProducts = document.querySelector('.index-products');
let searchTextForm = document.getElementById('search-text-form');
if (indexProducts || searchTextForm) {
    import(/* webpackPreload: true */ '../components/catalog/catalog').then(({default: catalogFilter}) => {
        new catalogFilter();
    }).catch(error => console.error(error.message));
}

/** Table */
let tables = document.querySelectorAll('.table-responsive:not(.disabled)');
if (tables.length > 0) {
    import(/* webpackPreload: true */ '../components/table').then(({default: tablesPlugin}) => {
        new tablesPlugin(tables);
    }).catch(error => console.error(error.message));
}

/** Maps */
let maps = document.querySelectorAll('.map-box');
if (maps.length > 0) {
    let mapModule = function () {
        if (!document.body.classList.contains('map-initialized')) {
            import('../components/map/map').then(({default: mapModule}) => {
                new mapModule(maps);
                document.body.classList.add('map-initialized');
            }).catch(error => console.error(error.message));
        }
    }
    if (isInViewport(maps[0])) {
        mapModule();
    } else {
        window.addEventListener('scroll', function (e) {
            mapModule();
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    let catalogMaps = document.querySelectorAll('.map-box-catalog');
    if (catalogMaps.length > 0) {
        import('../components/map/catalog-map').then(({default: mapModule}) => {
            new mapModule(catalogMaps);
        }).catch(error => console.error(error.message));
    }
});