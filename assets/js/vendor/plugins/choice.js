/**
 * Scroll to errors
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @inheritDoc https://github.com/jshjohnson/Choices
 */

import Choices from "choices.js";
import "choices.js/public/assets/styles/choices.css";

let trans = document.getElementById('data-translation')

export default function (selectors, returnElem = false) {

    let displayClear = function (selector, change) {
        let formGroup = selector.closest('.form-group')
        let value = selector.value
        if (formGroup) {
            let clearBtn = formGroup ? formGroup.querySelector('.choices__button') : null
            if (clearBtn && value) {
                clearBtn.classList.add('show')
            } else if (clearBtn && change) {
                clearBtn.classList.remove('show')
            }
        }
    }

    for (let i = 0; i < selectors.length; i++) {

        let trans = document.getElementById('data-translation')
        let select = selectors[i]
        let searchTrans = trans.dataset.hasOwnProperty('search') ? select.dataset.search : 'Rechercher';
        let searchPlaceholderValue = select.dataset.hasOwnProperty('searchPlaceholder') ? select.dataset.searchPlaceholder : searchTrans;
        let placeholderValue = select.getAttribute('placeholder') ? select.getAttribute('placeholder') : '';
        let noChoicesText = select.getAttribute('noChoicesText') ? select.getAttribute('noChoicesText') : '';
        let removeBtn = parseInt(select.dataset.remove) === 1

        const choice = new Choices(select, {
            searchEnabled: true,
            searchChoices: true,
            choices: [],
            placeholderValue: placeholderValue,
            noResultsText: trans.getAttribute('data-choices-no-result'),
            itemSelectText: '',
            noChoicesText: noChoicesText,
            removeItems: false,
            removeItemButton: false,
            searchPlaceholderValue: searchPlaceholderValue,
            shouldSort: false,
            // classNames: {
            //     containerOuter: ['selector-group', 'w-100']
            // },
            callbackOnInit: function (ev) {
                if (removeBtn) {
                    displayClear(select)
                }
            }
        });

        choice.passedElement.element.addEventListener('showDropdown', function (event) {
            let invalidGroup = select.closest('.form-group.is-invalid')
            if (invalidGroup) {
                invalidGroup.classList.remove('is-invalid')
                let feedbacks = invalidGroup.getElementsByClassName('invalid-feedback')
                if (feedbacks) {
                    feedbacks[0].remove()
                }
            }
        }, false);

        choice.passedElement.element.addEventListener('change', (event) => {
            if (removeBtn) {
                displayClear(select, true)
            }
        }, false);

        if(returnElem){
            return choice;
        }
    }
}
