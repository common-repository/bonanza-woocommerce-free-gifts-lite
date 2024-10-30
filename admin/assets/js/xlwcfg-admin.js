var xlwcfg_admin_change_content = null;
var current_pro_slug = null;

jQuery(document).ready(function ($) {
    'use strict';
    /**
     * Set up the functionality for CMB2 conditionals.
     */
    window.XLWCFG_CMB2ConditionalsInit = function (changeContext, conditionContext) {
        var loopI, requiredElms, uniqueFormElms, formElms;

        if ('undefined' === typeof changeContext) {
            changeContext = 'body';
        }
        changeContext = $(changeContext);

        if ('undefined' === typeof conditionContext) {
            conditionContext = 'body';
        }
        conditionContext = $(conditionContext);
        window.xlwcfg_admin_change_content = conditionContext;
        changeContext.on('change', 'input, textarea, select', function (evt) {
            var elm = $(this),
                fieldName = $(this).attr('name'),
                dependants,
                dependantsSeen = [],
                checkedValues,
                elmValue;

            var dependants = $('[data-xlwcfg-conditional-id="' + fieldName + '"]', conditionContext);
            if (!elm.is(":visible")) {
                return;
            }

            // Only continue if we actually have dependants.
            if (dependants.length > 0) {

                // Figure out the value for the current element.
                if ('checkbox' === elm.attr('type')) {
                    checkedValues = $('[name="' + fieldName + '"]:checked').map(function () {
                        return this.value;
                    }).get();
                } else if ('radio' === elm.attr('type')) {
                    if ($('[name="' + fieldName + '"]').is(':checked')) {
                        elmValue = elm.val();
                    }
                } else {
                    elmValue = evt.currentTarget.value;
                }

                dependants.each(function (i, e) {
                    var loopIndex = 0,
                        current = $(e),
                        currentFieldName = current.attr('name'),
                        requiredValue = current.data('xlwcfg-conditional-value'),
                        currentParent = current.parents('.cmb-row:first'),
                        shouldShow = false;


                    // Only check this dependant if we haven't done so before for this parent.
                    // We don't need to check ten times for one radio field with ten options,
                    // the conditionals are for the field, not the option.
                    if ('undefined' !== typeof currentFieldName && '' !== currentFieldName && $.inArray(currentFieldName, dependantsSeen) < 0) {
                        dependantsSeen.push = currentFieldName;

                        if ('checkbox' === elm.attr('type')) {
                            if ('undefined' === typeof requiredValue) {
                                shouldShow = (checkedValues.length > 0);
                            } else if ('off' === requiredValue) {
                                shouldShow = (0 === checkedValues.length);
                            } else if (checkedValues.length > 0) {
                                if ('string' === typeof requiredValue) {
                                    shouldShow = ($.inArray(requiredValue, checkedValues) > -1);
                                } else if (Array.isArray(requiredValue)) {
                                    for (loopIndex = 0; loopIndex < requiredValue.length; loopIndex++) {
                                        if ($.inArray(requiredValue[loopIndex], checkedValues) > -1) {
                                            shouldShow = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        } else if ('undefined' === typeof requiredValue) {
                            shouldShow = (elm.val() ? true : false);
                        } else {
                            if ('string' === typeof requiredValue) {
                                shouldShow = (elmValue === requiredValue);
                            }
                            if ('number' === typeof requiredValue) {
                                shouldShow = (elmValue == requiredValue);
                            } else if (Array.isArray(requiredValue)) {
                                shouldShow = ($.inArray(elmValue, requiredValue) > -1);
                            }
                        }

                        // Handle any actions necessary.
                        currentParent.toggle(shouldShow);
                        if (current.data('conditional-required')) {
                            current.prop('required', shouldShow);
                        }

                        // If we're hiding the row, hide all dependants (and their dependants).
                        if (false === shouldShow) {
                            // CMB2ConditionalsRecursivelyHideDependants(currentFieldName, current, conditionContext);
                        }

                        // If we're showing the row, check if any dependants need to become visible.
                        else {
                            if (1 === current.length) {
                                current.trigger('change');
                            } else {
                                current.filter(':checked').trigger('change');
                            }
                        }
                    } else {
                        /** Handling for */
                        if (current.hasClass("dtheme-cmb2-tabs") || current.hasClass("cmb2-xlwcfg_html")) {


                            if ('checkbox' === elm.attr('type')) {
                                if ('undefined' === typeof requiredValue) {
                                    shouldShow = (checkedValues.length > 0);
                                } else if ('off' === requiredValue) {
                                    shouldShow = (0 === checkedValues.length);
                                } else if (checkedValues.length > 0) {
                                    if ('string' === typeof requiredValue) {
                                        shouldShow = ($.inArray(requiredValue, checkedValues) > -1);
                                    } else if (Array.isArray(requiredValue)) {
                                        for (loopIndex = 0; loopIndex < requiredValue.length; loopIndex++) {
                                            if ($.inArray(requiredValue[loopIndex], checkedValues) > -1) {
                                                shouldShow = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            } else if ('undefined' === typeof requiredValue) {
                                shouldShow = (elm.val() ? true : false);
                            } else {
                                if ('string' === typeof requiredValue) {
                                    shouldShow = (elmValue === requiredValue);
                                }
                                if ('number' === typeof requiredValue) {
                                    shouldShow = (elmValue == requiredValue);
                                } else if (Array.isArray(requiredValue)) {
                                    shouldShow = ($.inArray(elmValue, requiredValue) > -1);
                                }
                            }

                        } else if (current.hasClass("xlwcfg_custom_wrapper_group") || current.hasClass("xlwcfg_custom_wrapper_wysiwyg")) {
                            if ('checkbox' === elm.attr('type')) {
                                if ('undefined' === typeof requiredValue) {
                                    shouldShow = (checkedValues.length > 0);
                                } else if ('off' === requiredValue) {
                                    shouldShow = (0 === checkedValues.length);
                                } else if (checkedValues.length > 0) {
                                    if ('string' === typeof requiredValue) {
                                        shouldShow = ($.inArray(requiredValue, checkedValues) > -1);
                                    } else if (Array.isArray(requiredValue)) {
                                        for (loopIndex = 0; loopIndex < requiredValue.length; loopIndex++) {
                                            if ($.inArray(requiredValue[loopIndex], checkedValues) > -1) {
                                                shouldShow = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            } else if ('undefined' === typeof requiredValue) {
                                shouldShow = (elm.val() ? true : false);
                            } else {
                                if ('string' === typeof requiredValue) {
                                    shouldShow = (elmValue === requiredValue);
                                }
                                if ('number' === typeof requiredValue) {
                                    shouldShow = (elmValue == requiredValue);
                                } else if (Array.isArray(requiredValue)) {
                                    shouldShow = ($.inArray(elmValue, requiredValue) > -1);
                                }
                            }

                        }
                    }
                });
            }
        });

        window.xlwcfg_admin_change_content.on("xlwcfg_conditional_runs", function (e, current, currentFieldName, requiredValue, currentParent, shouldShow, elm, elmValue) {

            var loopIndex = 0;
            var checkedValues;
            var shouldShow = false;
            if (typeof current.attr('data-xlwcfg-conditional-value') == "undefined") {
                return;
            }

            elm = $("[name='" + current.attr('data-xlwcfg-conditional-id') + "']", changeContext).eq(0);

            if (!elm.is(":visible")) {

                return;
            }
            // Figure out the value for the current element.
            if ('checkbox' === elm.attr('type')) {
                checkedValues = $('[name="' + current.attr('data-xlwcfg-conditional-id') + '"]:checked').map(function () {
                    return this.value;
                }).get();
            } else if ('radio' === elm.attr('type')) {
                elmValue = $('[name="' + current.attr('data-xlwcfg-conditional-id') + '"]:checked').val();

            }

            requiredValue = current.data('xlwcfg-conditional-value');

            // Only check this dependant if we haven't done so before for this parent.
            // We don't need to check ten times for one radio field with ten options,
            // the conditionals are for the field, not the option.
            if ('undefined' !== typeof currentFieldName && '' !== currentFieldName) {


                if ('checkbox' === elm.attr('type')) {
                    if ('undefined' === typeof requiredValue) {
                        shouldShow = (checkedValues.length > 0);
                    } else if ('off' === requiredValue) {
                        shouldShow = (0 === checkedValues.length);
                    } else if (checkedValues.length > 0) {
                        if ('string' === typeof requiredValue) {
                            shouldShow = ($.inArray(requiredValue, checkedValues) > -1);
                        } else if (Array.isArray(requiredValue)) {
                            for (loopIndex = 0; loopIndex < requiredValue.length; loopIndex++) {
                                if ($.inArray(requiredValue[loopIndex], checkedValues) > -1) {
                                    shouldShow = true;
                                    break;
                                }
                            }
                        }
                    }
                } else if ('undefined' === typeof requiredValue) {
                    shouldShow = (elm.val() ? true : false);
                } else {

                    if ('string' === typeof requiredValue) {
                        shouldShow = (elmValue === requiredValue);
                    }
                    if ('number' === typeof requiredValue) {
                        shouldShow = (elmValue == requiredValue);
                    } else if (Array.isArray(requiredValue)) {

                        shouldShow = ($.inArray(elmValue, requiredValue) > -1);
                    }
                }

                // Handle any actions necessary.
                currentParent.toggle(shouldShow);

                if (current.data('conditional-required')) {
                    current.prop('required', shouldShow);
                }

                // If we're hiding the row, hide all dependants (and their dependants).
                if (false === shouldShow) {
                    // CMB2ConditionalsRecursivelyHideDependants(currentFieldName, current, conditionContext);
                }

                // If we're showing the row, check if any dependants need to become visible.
                else {
                    if (1 === current.length) {
                        current.trigger('change');
                    } else {
                        current.filter(':checked').trigger('change');
                    }
                }
            } else {


                if (current.hasClass("dtheme-cmb2-tabs") || current.hasClass("cmb2-xlwcfg_html")) {


                    if ('checkbox' === elm.attr('type')) {
                        if ('undefined' === typeof requiredValue) {
                            shouldShow = (checkedValues.length > 0);
                        } else if ('off' === requiredValue) {
                            shouldShow = (0 === checkedValues.length);
                        } else if (checkedValues.length > 0) {
                            if ('string' === typeof requiredValue) {
                                shouldShow = ($.inArray(requiredValue, checkedValues) > -1);
                            } else if (Array.isArray(requiredValue)) {
                                for (loopIndex = 0; loopIndex < requiredValue.length; loopIndex++) {
                                    if ($.inArray(requiredValue[loopIndex], checkedValues) > -1) {
                                        shouldShow = true;
                                        break;
                                    }
                                }
                            }
                        }
                    } else if ('undefined' === typeof requiredValue) {
                        shouldShow = (elm.val() ? true : false);
                    } else {
                        if ('string' === typeof requiredValue) {
                            shouldShow = (elmValue === requiredValue);
                        }
                        if ('number' === typeof requiredValue) {
                            shouldShow = (elmValue == requiredValue);
                        } else if (Array.isArray(requiredValue)) {
                            shouldShow = ($.inArray(elmValue, requiredValue) > -1);
                        }
                    }

                    currentParent.toggle(shouldShow);

                } else if (current.hasClass("xlwcfg_custom_wrapper_group") || current.hasClass("xlwcfg_custom_wrapper_wysiwyg")) {
                    if ('checkbox' === elm.attr('type')) {
                        if ('undefined' === typeof requiredValue) {
                            shouldShow = (checkedValues.length > 0);
                        } else if ('off' === requiredValue) {
                            shouldShow = (0 === checkedValues.length);
                        } else if (checkedValues.length > 0) {
                            if ('string' === typeof requiredValue) {
                                shouldShow = ($.inArray(requiredValue, checkedValues) > -1);
                            } else if (Array.isArray(requiredValue)) {
                                for (loopIndex = 0; loopIndex < requiredValue.length; loopIndex++) {
                                    if ($.inArray(requiredValue[loopIndex], checkedValues) > -1) {
                                        shouldShow = true;
                                        break;
                                    }
                                }
                            }
                        }
                    } else if ('undefined' === typeof requiredValue) {
                        shouldShow = (elm.val() ? true : false);
                    } else {
                        if ('string' === typeof requiredValue) {
                            shouldShow = (elmValue === requiredValue);
                        }
                        if ('number' === typeof requiredValue) {
                            shouldShow = (elmValue == requiredValue);
                        } else if (Array.isArray(requiredValue)) {
                            shouldShow = ($.inArray(elmValue, requiredValue) > -1);
                        }
                    }

                    current.toggle(shouldShow);

                }
            }
        });

        $('[data-xlwcfg-conditional-id]', conditionContext).not(".xlwcfg_custom_wrapper_group").parents('.cmb-row:first').hide({
            "complete": function () {
                $("body").trigger("xlwcfg_w_trigger_conditional_on_load");

                uniqueFormElms = [];
                $(':input', changeContext).each(function (i, e) {
                    var elmName = $(e).attr('name');
                    if ('undefined' !== typeof elmName && '' !== elmName && -1 === $.inArray(elmName, uniqueFormElms)) {
                        uniqueFormElms.push(elmName);
                    }
                });

                for (loopI = 0; loopI < uniqueFormElms.length; loopI++) {
                    formElms = $('[name="' + uniqueFormElms[loopI] + '"]');
                    if (1 === formElms.length || !formElms.is(':checked')) {
                        formElms.trigger('change');
                    } else {
                        formElms.filter(':checked').trigger('change');
                    }
                }

            }
        });

        $(document).on('xlwcfg_cmb2_options_tabs_activated', function (e, panel) {

            var uniqueFormElms = [];
            $(':input', ".cmb-tab-panel").each(function (i, e) {
                var elmName = $(e).attr('name');
                if ('undefined' !== typeof elmName && '' !== elmName && -1 === $.inArray(elmName, uniqueFormElms) && $(e).is(":visible")) {
                    uniqueFormElms.push(elmName);
                }
            });
            for (loopI = 0; loopI < uniqueFormElms.length; loopI++) {
                formElms = $('[name="' + uniqueFormElms[loopI] + '"]');
                if (1 === formElms.length || !formElms.is(':checked')) {
                    formElms.trigger('change');
                } else {
                    formElms.filter(':checked').trigger('change');
                }
            }
        });
        $(document).on('xlwcfg_acc_toggled', function (e, elem) {
            var uniqueFormElms = [];
            $(':input', ".ui-tabs-panel").each(function (i, e) {
                var elmName = $(e).attr('name');
                if ('undefined' !== typeof elmName && '' !== elmName && -1 === $.inArray(elmName, uniqueFormElms) && $(e).is(":visible")) {
                    uniqueFormElms.push(elmName);
                }
            });
            for (loopI = 0; loopI < uniqueFormElms.length; loopI++) {
                formElms = $('[name="' + uniqueFormElms[loopI] + '"]');
                if (1 === formElms.length || !formElms.is(':checked')) {
                    formElms.trigger('change');
                } else {
                    formElms.filter(':checked').trigger('change');
                }
            }
        });


    }

    if (typeof pagenow !== "undefined" && "xlwcfg_free_gift" == pagenow) {
        XLWCFGCMB2ConditionalsInit('#post .cmb2-wrap.xlwcfg_options_common', '#post .cmb2-wrap.xlwcfg_options_common');
        XLWCFG_CMB2ConditionalsInit('#post .cmb2-wrap.xlwcfg_options_common', '#post  .cmb2-wrap.xlwcfg_options_common');
    }

    if ($('.xlwcfg_global_option .xlwcfg_options_page_left_wrap').length > 0) {
        $('.xlwcfg_global_option .xlwcfg_options_page_left_wrap').removeClass('dispnone');
    }

    $(window).on('load', function (e) {
        if ($(".xlwcfg_cmb2_product_chosen select").length > 0) {
            $(".xlwcfg_cmb2_product_chosen select").xlAjaxChosen({
                type: 'GET',
                minTermLength: 3,
                afterTypeDelay: 500,
                data: {
                    'action': 'woocommerce_json_search_products_and_variations',
                    'security': XLWCFGParams.search_products_nonce,
                },
                url: ajaxurl,
                dataType: 'json'
            }, function (data) {
                var results = [];
                $.each(data, function (i, val) {
                    results.push({value: i, text: val});
                });
                console.log(results);
                return results;
            }).change(function () {
                var $this = $(this);
            });
        }
    });
});

function show_modal_pro(defaultVal) {
    console.log(defaultVal);
    current_pro_slug = defaultVal;
    current_pro_slug = current_pro_slug.replace("#", '');
    var defaults = {
        title: "",
        icon: 'dashicons dashicons-lock',
        content: "",
        confirmButton: buy_pro_helper.call_to_action_text,
        columnClass: 'modal-wide',
        closeIcon: true,
        confirm: function () {
            var replaced = buy_pro_helper.buy_now_link.replace("{current_slug}", current_pro_slug);
            window.open(replaced, '_blank');
        }
    };

    if (buy_pro_helper.popups[defaultVal] !== "undefined") {
        var data = buy_pro_helper.popups[defaultVal];

        data = jQuery.extend(true, {}, defaults, data);

    } else {
        var data = {};
        data = jQuery.extend(true, {}, defaults, data);
    }


    jQuery.xlAlert(data);
}