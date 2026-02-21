/**
 * Module : Vendor Functions Form
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

import {displayLoader, hideLoader} from "../front/components/form/loader";

/**
 * To reset form.
 */
export function resetForm(form) {

    const resetForm = !(form.dataset.reset && parseInt(form.dataset.reset) === 0);
    const formId = form.getAttribute('id');

    if (resetForm) {
        form.reset();
    }

    document.getElementById(formId).querySelectorAll('.form-control').forEach(function (input) {
        if (input.type && input.type === 'checkbox') {
            input.checked = false;
        } else {
            input.value = "";
        }
    });

    document.getElementById(formId).querySelectorAll('.form-check-input').forEach(function (checkbox) {
        if (checkbox.type && checkbox.type === 'checkbox') {
            checkbox.checked = false;
        }
    });
}

/**
 * To display form loader.
 */
export function displayFormLoader(form) {
    const formContainer = form.closest('.form-container');
    if (formContainer) {
        displayLoader(formContainer);
    }
}

/**
 * To hide form loader.
 */
export function hideFormLoader(form) {
    const formContainer = form.closest('.form-container');
    if (formContainer) {
        hideLoader(formContainer);
    }
}

/**
 * To refresh form.
 */
export function refreshForm(event, submitBtn) {

    const alertBlock = document.getElementById('alert-form-block');
    if (alertBlock) {
        alertBlock.classList.add('d-none');
    }

    document.querySelectorAll('.alert-success').forEach(function (alert) {
        alert.remove();
    });

    let container = submitBtn.closest('.form-container');
    let validFiles = checkInputsFiles(submitBtn);
    let form = submitBtn.closest('form');

    if (!validFiles) {
        event.preventDefault();
        hideLoader(container);
        return true;
    } else if (validFiles && !form.classList.contains('form-ajax')) {
        // form.unbind('submit').submit();
    }

    if (!submitBtn.classList.contains('form-ajax')) {
        displayLoader(container);
    }
}

/**
 * On focus.
 */
export function onFocus(form) {
    let inputs = form.querySelectorAll('.form-control');
    inputs.forEach(function (input) {
        const formGroup = input.closest('.form-group')
        if (formGroup) {
            input.addEventListener('focus', () => {
                formGroup.classList.toggle('focus');
            })
            input.addEventListener('blur', () => {
                formGroup.classList.toggle('focus');
            })
        }
        const inputGroup = input.closest('.input-group')
        if (inputGroup) {
            input.addEventListener('focus', () => {
                inputGroup.classList.toggle('focus');
            })
            input.addEventListener('blur', () => {
                inputGroup.classList.toggle('focus');
            })
        }
    });
}

/**
 * To add selected class on select changed.
 */
export function selectChange() {
    let selects = document.querySelectorAll('select');
    selects.forEach(function (select) {
        select.addEventListener('change', () => {
            if (select.value) {
                select.classList.add('selected');
            } else {
                select.classList.remove('selected');
            }
        });
    });
}

/**
 * Set filename on input file change.
 */
export function fileChange() {
    let inputsFile = document.querySelectorAll('input[type="file"]');
    inputsFile.forEach(function (input) {
        input.addEventListener('change', (event) => {
            let fileName = event.target.files[0].name;
            let inputParent = input.parentNode;
            input.setAttribute('placeholder', fileName);
            if (inputParent) {
                let label = inputParent.querySelector('.custom-file-label');
                if (label) {
                    label.innerHTML = fileName;
                }
            }
        });
    });
}

/**
 * To check input files.
 */
export function checkInputsFiles(el) {
    let isValid = true;
    let form = el.closest('form');
    let inputsFileMultiple = form.querySelectorAll('input[type="file"][multiple="multiple"]');
    if (inputsFileMultiple.length > 0) {
        let inputs = form.querySelectorAll('input');
        inputs.forEach(function (input) {
            let isRequired = input.hasAttribute('required');
            if (isRequired && !input.value || isRequired && input.type === 'checkbox' && !input.checked) {
                isValid = false;
                document.getElementById('alert-form-block').classList.remove('d-none');
            }
        });
    }
    return isValid;
}
