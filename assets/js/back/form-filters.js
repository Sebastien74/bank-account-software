/**
 * Form filters.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

export default function (el) {

    const action = el.getAttribute('action');
    const elId = el.getAttribute('id');

    const bind = function () {

        const form = document.getElementById(elId);

        if (!form) return;

        form.querySelectorAll('select').forEach(select => {

            // Avoid multiple bindings if you re-bind
            select.onchange = null;

            select.addEventListener('change', () => {

                const loader = document.querySelector('.stripe-preloader');
                loader.classList.remove('d-none');

                // ✅ Read values from CURRENT form DOM, not from the old `el`
                const params = new URLSearchParams(new FormData(form));

                history.pushState({}, null, action + (action.includes('?') ? '&' : '?') + params.toString());

                params.set('ajax', 'true');

                const url = action + (action.includes('?') ? '&' : '?') + params.toString();

                const xHttp = new XMLHttpRequest();
                xHttp.open('GET', url, true);
                xHttp.setRequestHeader('Accept', 'application/json');

                xHttp.onload = function () {

                    if (this.status === 200) {

                        let response = this.responseText;
                        response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                        response = JSON.parse(response);

                        const html = document.createElement('div');
                        html.innerHTML = response.html;

                        const container = document.querySelector('#entities-index-page');
                        container.innerHTML = html.querySelector('#entities-index-page').innerHTML;

                        container.querySelectorAll('.pagination').forEach(pagination => {
                            pagination.querySelectorAll('.page-link').forEach(link => {
                                if (link.href) {
                                    link.href = link.href.replace(/&ajax=true/, '');
                                }
                            });
                        });

                        loader.classList.add('d-none');

                        const selects = document.querySelectorAll('select');
                        if (selects.length > 0) {
                            import('./choice').then(({default: Choice}) => {
                                new Choice(selects);
                            }).catch(error => console.error(error.message));
                        }
                        import('./bootstrap').then(({default: Bootstrap}) => {
                            new Bootstrap();
                        }).catch(error => console.error(error.message));

                        import('./display-loader').then(({default: DisplayLoader}) => {
                            new DisplayLoader();
                        }).catch(error => console.error(error.message));

                        // Re-bind events on the newly injected DOM
                        bind();
                    }
                };

                xHttp.send(null);
            });
        });
    };

    bind();
}
