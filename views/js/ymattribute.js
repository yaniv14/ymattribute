const has_own_property = Object.prototype.hasOwnProperty;
first_url_check = false;
const attributeDisabler = {
    currentCombination: {},
    selectInput: null,
    radioInput: null,
    colorInput: null,
    colorDisplay: null,

    tempDisable: false,

    init: () => {
        // Define object variables
        attributeDisabler.selectInput = $(`${productAttributesClass} select[name^=group_]`);
        attributeDisabler.radioInput = $(`${productAttributesClass} input[type=radio][name^=group_]`);
        attributeDisabler.colorInput = $(`${productAttributesClass} input[type=hidden][name^=group_]`);
        attributeDisabler.colorDisplay = $(`${productAttributesClass} .color_pick`);

        if ((attributeDisabler.selectInput.length > 0 || attributeDisabler.radioInput.length > 0 || attributeDisabler.colorInput.length > 0 || attributeDisabler.colorDisplay.length) && availableCombinations) {
            // Bind event listeners
            attributeDisabler.selectInput.on('change', function (e) {
                // Prevent product.js from doing anything as we set the new combination
                if (attributeDisabler.tempDisable) {
                    return attributeDisabler.stopPropagation(e);
                }

                attributeDisabler.updateInputs()
            });

            attributeDisabler.radioInput.on('click', function (e) {
                // Prevent product.js from doing anything as we set the new combination
                if (attributeDisabler.tempDisable) {
                    return attributeDisabler.stopPropagation(e);
                }

                attributeDisabler.updateInputs()
            });

            // Unbind any inline events and re-register them as page events
            attributeDisabler.selectInput.each(function () {
                let selectChange = $(this).prop('onchange');
                if (selectChange) {
                    $(this).removeProp('onchange');
                    $(document).on('change', attributeDisabler.getPath($(this)), selectChange)
                }
            });

            attributeDisabler.radioInput.each(function () {
                let radioClick = $(this).prop('onclick');
                if (radioClick) {
                    $(this).removeProp('onclick');
                    $(document).on('click', attributeDisabler.getPath($(this)), radioClick)
                }
            });

            attributeDisabler.colorDisplay.on('click', function (e) {
                // Prevent prestashop doing anything as we set the new combination
                if (attributeDisabler.tempDisable) {
                    return attributeDisabler.stopPropagation(e);
                }

                setTimeout(function () {
                    attributeDisabler.updateInputs()
                }, 50)
            });

            // Unbind any inline events and re-register them as page events
            attributeDisabler.colorDisplay.each(function () {
                let colorClick = $(this).prop('onclick');
                if (colorClick) {
                    $(this).removeProp('onclick');
                    $(document).on('click', attributeDisabler.getPath($(this)), colorClick)
                }
            });

            // Update the inputs
            attributeDisabler.updateInputs();

            return true
        }
        else if (availableCombinations) {
            console.log('Unable to find matching attributes by class.');
            return false
        }
    },

    stopPropagation: (e) => {
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
        if (e.cancelBubble) e.cancelBubble = true;
        if (e.returnValue) e.returnValue = false
    },

    sortByKey: (data) => {
        let keys = [];
        let sorted = {};

        for (let i in data) {
            if (has_own_property.call(data, i)) {
                keys.push(i);
            }
        }

        keys.sort();

        for (let i = 0, len = keys.length; i < len; i++) {
            sorted[keys[i]] = data[keys[i]];
        }

        return sorted
    },

    getPath: (element) => {
        let path = [];

        while (element.length) {
            let coreNode = element[0];
            let selector = coreNode.localName;
            let parent = element.parent();

            if (!selector) {
                break;
            } else {
                selector = selector.toLowerCase();
            }

            if (parent.children(selector).length > 1) {
                let children = parent.children();
                let index = children.index(coreNode) + 1;

                if (index > 1) {
                    selector += ':nth-child(' + index + ')'
                }
            }

            path.unshift(selector);
            element = parent
        }

        return path.join(' > ')
    },

    combinationExist: (comparison) => {
        for (let i in availableCombinations)
            if (has_own_property.call(availableCombinations, i)) {
                if (JSON.stringify(attributeDisabler.sortByKey(availableCombinations[i])) === JSON.stringify(attributeDisabler.sortByKey(comparison))) {
                    return true
                }
            }
        return false
    },

    getIDFromName: (name, key) => {
        return parseInt(name.replace(key, ''))
    },

    getCurrentCombination: () => {
        let currentCombination = {};

        attributeDisabler.selectInput.each(function () {
            let groupID = attributeDisabler.getIDFromName($(this).attr('name'), 'group_');
            currentCombination[groupID] = this.value;
        });

        attributeDisabler.radioInput.filter(':checked').each(function () {
            let groupID = attributeDisabler.getIDFromName($(this).attr('name'), 'group_');
            currentCombination[groupID] = this.value;
        });

        attributeDisabler.colorInput.each(function () {
            let groupID = attributeDisabler.getIDFromName($(this).attr('name'), 'group_');
            currentCombination[groupID] = this.value;
        });

        return currentCombination
    },

    setCombination: (combination) => {
        let triggerUpdate;

        // Temporarily stop any changes from processing so we can update the current selection
        attributeDisabler.tempDisable = true;

        for (let i in combination) {
            if (has_own_property.call(combination, i)) {
                if (attributeDisabler.selectInput.filter('[name="group_' + i + '"]').length) {
                    attributeDisabler.selectInput.filter('[name="group_' + i + '"]').val(combination[i]);
                    triggerUpdate = function (i) {
                        return () => {
                            attributeDisabler.selectInput.filter('[name="group_' + i + '"]').trigger('change')
                        }
                    }(i);
                    triggerUpdate()
                }
                else if (attributeDisabler.radioInput.filter('[name="group_' + i + '"]').length) {
                    attributeDisabler.radioInput.filter('[name="group_' + i + '"]').removeAttr('checked').filter('[value=' + combination[i] + ']').attr('checked', 'checked');
                    triggerUpdate = function (i) {
                        return () => {
                            attributeDisabler.radioInput.filter('[name="group_' + i + '"][value=' + combination[i] + ']').trigger('click')
                        }
                    }(i);
                    triggerUpdate()
                }
                else if (attributeDisabler.colorInput.filter('[name="group_' + i + '"]').length) {
                    attributeDisabler.colorInput.filter('[name="group_' + i + '"]').val(combination[i]);
                    triggerUpdate = function (i) {
                        return () => {
                            attributeDisabler.colorDisplay.filter('#color_' + combination[i]).trigger('click')
                        }
                    }(i);
                    triggerUpdate()
                }
            }
        }

        attributeDisabler.tempDisable = false;

        // Once everything is valid trigger a change to force the display update
        if (triggerUpdate) {
            attributeDisabler.updateInputs();
            triggerUpdate();
            return true
        }
        return false
    },

    updateInputs: () => {
        // Empty out the current selection to re-fill it
        attributeDisabler.currentCombination = attributeDisabler.getCurrentCombination();

        // Hide all elements and re-enable them if they're part of the combination
        // Completely prevent the user selecting an invalid input
        // attributeDisabler.selectInput.find('option').attr('disabled', 'disabled')
        // attributeDisabler.radioInput.attr('disabled', 'disabled')

        // Mark invalid inputs so we know to auto-select a valid one if chosen
        attributeDisabler.selectInput.find('option').addClass('oos_attribute').prop('disabled', true);
        attributeDisabler.radioInput.addClass('oos_attribute').prop('disabled', true);
        attributeDisabler.radioInput.parent().addClass('oos_attribute').addClass('radio_oos_attribute');

        // Re-enable selectors if they're part of a valid combination
        attributeDisabler.selectInput.each(function () {
            let groupID = attributeDisabler.getIDFromName($(this).attr('name'), 'group_');
            let comparison = $.extend({}, attributeDisabler.currentCombination);

            $(this).find('option').each(function () {
                comparison[groupID] = $(this).val();

                if (attributeDisabler.combinationExist(comparison)) {
                    $(this).removeClass('oos_attribute').prop('disabled', false);
                    $(this).text($(this).attr('title'));
                } else {
                    $(this).text($(this).attr('title') + ' (' + outOfStockText + ')');
                }
            })
        });

        // Re-enable radios if they're part of a valid combination
        attributeDisabler.radioInput.each(function () {
            let groupID = attributeDisabler.getIDFromName($(this).attr('name'), 'group_');
            let comparison = $.extend({}, attributeDisabler.currentCombination);

            comparison[groupID] = $(this).val();

            if (attributeDisabler.combinationExist(comparison)) {
                $(this).removeClass('oos_attribute').prop('disabled', false);
                $(this).parent().removeClass('oos_attribute').removeClass('radio_oos_attribute');
            }
        });

        // Hide all elements and re-enable them if they're part of the combination
        // attributeDisabler.colorDisplay.parent().stop().fadeOut(500);
        attributeDisabler.colorDisplay.addClass('oos_attribute').prop('disabled', true);
        attributeDisabler.colorDisplay.parent().addClass('oos_attribute').prop('disabled', true);

        // Re-enable color pickers if they're part of a valid combination
        attributeDisabler.colorDisplay.each(function () {
            let groupName = $(this).closest('.attribute_list').find('input[type=hidden][name^=group_]').attr('name');
            let groupID = attributeDisabler.getIDFromName(groupName, 'group_');
            let colorID = attributeDisabler.getIDFromName($(this).attr('id'), 'color_');
            let comparison = $.extend({}, attributeDisabler.currentCombination);

            comparison[groupID] = colorID.toString();

            if (attributeDisabler.combinationExist(comparison)) {
                $(this).removeClass('oos_attribute').prop('disabled', false);
                $(this).parent().removeClass('oos_attribute').prop('disabled', false);
            }
        })
    }
};

$(function () {
    attributeDisabler.init();
});