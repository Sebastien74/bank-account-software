/** Import CSS */
import '../../../../scss/front/default/templates/home.scss';

/** Import JS */

/** Search form filters */
let searchFormsFilters = document.querySelectorAll('.entities-filters-form');
if (searchFormsFilters) {
    import(/* webpackPreload: true */ '../components/entities-filters').then(({default: searchForm}) => {
        new searchForm(searchFormsFilters);
    }).catch(error => console.error(error.message));
}