/**
 * Boostrap Collapse.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import Collapse from './bootstrap/collapse';

export default function (els) {
    els.forEach(el => {
        if (!el.classList.contains('loaded')) {
            el.classList.add('loaded');
            new Collapse(el, {toggle: false});
        }
    });
}
