/**
 * Display loader.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    document.querySelectorAll('.display-loader').forEach(el => {
        el.onclick = function () {
            const loader = document.querySelector('.stripe-preloader');
            loader.classList.remove('d-none');
        }
    });
}
