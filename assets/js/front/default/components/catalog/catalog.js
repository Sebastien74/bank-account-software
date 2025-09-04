import Tooltip from "../../../bootstrap/dist/tooltip";
import {hideLoader, displayLoader} from "../loader";
import {AjaxPagination} from "../../functions";

export default function () {

    let indexProducts = document.getElementById('index-products');
    hideLoader(indexProducts);
    AjaxPagination(indexProducts);

    let fields = function (form) {

        let selectors = form ? form.querySelectorAll('.select-search') : [];
        if (selectors && selectors.length > 0) {
            // import('../../../../vendor/plugins/choice').then(({default: choices}) => {
            //     new choices(selectors)
            selectors.forEach((selector) => {
                const group = selector.closest('.group');
                const resetBtn = group ? group.querySelector('.clear') : false;
                if (resetBtn) {
                    resetBtn.onclick = function () {
                        selector.value = '';
                        post(form);
                    }
                }
                selector.addEventListener('change', (event) => {
                    // let initialValue = selector.value;
                    // let changeValue = event.detail.value;
                    // if (initialValue === changeValue) {
                    post(form);
                    // }
                }, false);
            });
            // }).catch(error => console.log(error.message));
        }

        let btnCheckboxesGroups = form ? form.querySelectorAll('.btn-group-toggle') : [];
        btnCheckboxesGroups.forEach((checkboxGroup) => {
            let label = checkboxGroup.querySelector('label');
            let input = checkboxGroup.querySelector('input');
            input.addEventListener('change', (event) => {
                label.classList.toggle('active');
                post(form);
                event.stopImmediatePropagation();
            });
        });
    }

    /** Search by text */
    let keyDown = function () {
        let searchInputs = indexProducts.querySelectorAll('input[type="search"]');
        searchInputs.forEach((inputText) => {
            let group = inputText.closest('.input-group');
            let submitText = group.querySelector('.input-group-text');
            let keyDownEvent = function (event) {
                if (event.keyCode === 13 || event.which === 13) {
                    submitText.click();
                    event.preventDefault();
                    return false;
                }
            }
            if (inputText) {
                inputText.addEventListener("keydown", keyDownEvent);
                submitText.onclick = function () {
                    let form = inputText.closest('form');
                    post(form);
                }
            }
        });
    }
    keyDown();

    /** Search by filters */
    let form = document.getElementById('search-filter-form');
    if (form) {
        fields(form);
    }

    let post = function (form) {

        displayLoader(indexProducts, false);
        let url = removeParam(form, 'search_terms');
        let action = url ? form.getAttribute('action') + url + '&ajax=true' : form.getAttribute('action') + '?ajax=true';
        let pathname = window.location.pathname;

        let xHttp = new XMLHttpRequest();
        xHttp.open("GET", action, true);
        xHttp.send();
        xHttp.onload = function () {
            if (this.readyState === 4 && this.status === 200) {
                let response = this.response;
                response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                response = JSON.parse(response);
                let html = document.createElement('div');
                html.innerHTML = response.html;
                let container = document.getElementById('results');
                let rspContainer = html.querySelector('#results');
                container.innerHTML = rspContainer.innerHTML;
                window.history.replaceState({}, document.title, pathname + url);
                let scrollWrapper = html.querySelector('#scroll-wrapper');
                let docWrapper = document.querySelector('#scroll-wrapper');
                if (docWrapper) {
                    docWrapper.dataset.page = scrollWrapper.dataset.page;
                    docWrapper.dataset.max = scrollWrapper.dataset.max;
                }
                if (container) {
                    container.dataset.page = scrollWrapper.dataset.page;
                    container.dataset.max = scrollWrapper.dataset.max;
                }
                let showMoreDoc = document.querySelector('#show-more-wrap');
                if (showMoreDoc && parseInt(container.dataset.max) > 1) {
                    showMoreDoc.classList.remove('d-none');
                } else if (showMoreDoc && parseInt(container.dataset.max) <= 1) {
                    showMoreDoc.classList.add('d-none');
                }
                let tooltips = container.querySelectorAll('[data-bs-toggle="tooltip"]');
                for (let i = 0; i < tooltips.length; i++) {
                    let tooltipEl = tooltips[i];
                    new Tooltip(tooltipEl);
                }
                let resultCounter = document.querySelector('#result-counter');
                let rspCounter = html.querySelector('#result-counter');
                if (resultCounter && rspCounter) {
                    resultCounter.classList.remove('d-none');
                    resultCounter.innerHTML = rspCounter.innerHTML;
                }
                let formContainer = document.getElementById('search-products-filters-container');
                let rspFormContainer = html.querySelector('#search-products-filters-container');
                if (formContainer && rspFormContainer) {
                    formContainer.innerHTML = rspFormContainer.innerHTML
                    fields(formContainer.querySelector('#search-filter-form'));
                    let searchTextForm = formContainer.querySelector('#search-text-form');
                    if (searchTextForm) {
                        let submitText = searchTextForm.querySelector('.input-group-text');
                        submitText.onclick = function () {
                            post(searchTextForm);
                        }
                    }
                }
                AjaxPagination(html);
                keyDown();
                hideLoader(indexProducts);
            }
        }
    }

    let removeParam = function (form, parameter) {
        let sourceURL = '?' + decodeURI(new URLSearchParams(Array.from(new FormData(form))).toString());
        let urlParts = sourceURL.split('?');
        if (urlParts.length >= 2) {
            let urlBase = urlParts.shift();
            let queryString = urlParts.join("?");
            let prefix = encodeURIComponent(parameter) + '=';
            let parameters = queryString.split(/[&;]/g);
            for (let i = parameters.length; i-- > 0;) {
                let values = parameters[i].split('=');
                if (!values[values.length - 1]) {
                    parameters.splice(i, 1);
                } else if (parameters[i].lastIndexOf(prefix, 0) !== -1) {
                    parameters.splice(i, 1);
                }
            }
            sourceURL = urlBase + '?' + parameters.join('&');
        }
        return sourceURL === '?' ? '' : sourceURL;
    }
}