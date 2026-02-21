/**
 * Button group toggle.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    document.querySelectorAll('.btn-group-toggle').forEach(el => {
        el.onclick = function (e) {
            let input = el.querySelector('input');
            let label = el.querySelector('label');
            if (input.checked) {
                label.classList.add('active');
            } else {
                label.classList.remove('active');
            }
        }
    });
}
