import '../../../../scss/front/default/components/form/_form.scss';
import '../../../../scss/front/default/components/_steps-form.scss';

/**
 * Form Steps
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let body = $('body');

    $(function () {

        let previousStep = function (element) {
            element.on('click', '.step-form-ajax .btn-previous', function () {
                let previous = $(this).data('previous');
                element.find('.step-form-container').addClass('d-none');
                element.find('div.step-form-container[data-step="' + previous + '"]').removeClass('d-none');
                tabsAdvanced(body, previous);
            });
        };

        let tabsAdvanced = function (element, step) {
            element.find('.step-tab').each(function () {
                let stepTab = $(this);
                let stepTabId = stepTab.data('step');
                if (stepTabId <= step && !stepTab.hasClass('done')) {
                    stepTab.addClass('done');
                } else if (stepTabId > step) {
                    stepTab.removeClass('done');
                }
            });
        };

        previousStep(body);

        body.on('click', '.step-form-ajax [type="submit"]', function handler(e) {

            e.preventDefault();

            let el = $(this);
            let form = el.closest('form');
            let formId = form.attr('id');
            let formData = new FormData(document.getElementById(formId));
            let currentStep = el.data('step');
            let next = el.data('next');

            let loader = el.closest('.form-ajax-container').find('.loader.next');
            if (el.hasClass('last')) {
                loader = el.closest('.form-ajax-container').find('.loader.send');
            }

            $.ajax({
                url: form.attr('action') + '?advancement=' + el.data('advancement'),
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                async: true,
                beforeSend: function () {

                    loader.removeClass('d-none');

                    /** Remove errors */
                    import('../../../vendor/components/remove-errors').then(({default: removeErrors}) => {
                        new removeErrors();
                    }).catch(error => console.error(error.message));
                },
                success: function (response) {

                    let html = response.html;
                    let form = $(html).find('#' + formId);
                    let render = form.closest('.form-ajax-container').html();
                    let container = body.find('#' + formId).closest('.form-ajax-container');
                    let hasSuccess = response.success && el.hasClass('last');

                    container.html(render);

                    previousStep($(container));

                    if (hasSuccess) {

                        $('#' + formId + ' .form-control').val('');
                        $("input[type='checkbox']").prop('checked', false);

                        if (response.showModal) {
                            let modal = $(form.data('modal'));
                            modal.modal('show');
                            setTimeout(function () {
                                modal.modal('hide');
                            }, 3000);
                        }
                    } else {

                        let steps = $(container).find('.step-form-container');
                        steps.addClass('d-none');

                        steps.each(function () {

                            let step = $(this);
                            let stepId = step.data('step');
                            let invalids = step.find('.invalid-feedback');

                            if (invalids.length > 0 && currentStep !== stepId) {
                                step.find('.invalid-feedback').remove();
                                step.find('.is-invalid').removeClass('is-invalid');
                            }

                            if (invalids.length === 0 && currentStep === stepId) {
                                $('div.step-form-container[data-step="' + next + '"]').removeClass('d-none');
                                tabsAdvanced(body, next);
                            } else if (invalids.length > 0 && currentStep === stepId) {
                                step.removeClass('d-none');
                            }
                        });
                    }

                    /** Lottie */
                    import('../../../vendor/plugins/lottie-icon').then(({default: lottiePlugin}) => {
                        new lottiePlugin();
                    }).catch(error => console.error(error.message));

                    /** Form packages */
                    import('../trash/packages/form').then(({default: formPackage}) => {
                        new formPackage(body);
                    }).catch(error => console.error(error.message));
                },
                error: function (error) {
                }
            });

            e.stopImmediatePropagation();
            return false;
        });
    });
}