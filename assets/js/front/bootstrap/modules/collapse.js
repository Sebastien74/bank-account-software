/**
 * Collapse.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
export default function () {
    import(/* webpackPreload: true */ '../dist/collapse').then(({default: Collapse}) => {

    }).catch(error => console.error(error.message));
}