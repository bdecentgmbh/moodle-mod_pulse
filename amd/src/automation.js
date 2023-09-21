define("mod_pulse/automation", ['jquery', 'core/modal_factory', 'core/templates', 'core/str'], function($, Modal, Template, Str) {

    const moveOutMoreMenu = (navMenu) => {

        if (navMenu === null) {
            return;
        }

        var menu = navMenu.querySelector('a.automation-templates');

        if (menu === null) {
            return;
        }

        menu = menu.parentNode;
        menu.dataset.forceintomoremenu = false,
        menu.querySelector('a').classList.remove('dropdown-item');
        menu.querySelector('a').classList.add('nav-link');
        menu.parentNode.removeChild(menu);

        // Insert the stored menus before the more menu.
        navMenu.insertBefore(menu, navMenu.children[1]);
        window.dispatchEvent(new Event('resize')); // Dispatch the resize event to create more menu.
    };

    const returnToFailedTab = () => {

        if (document.forms['pulse-automation-template'] === null) {
            return false;
        }

        document.forms['pulse-automation-template'].onsubmit = (e) => {
            var form = e.target;
            var invalidElement = form.querySelector('.is-invalid');
            if (invalidElement === null) {
                return true;
            }

            var tabid = invalidElement.parentNode.parentNode.parentNode.id;
            var hrefSelector = '[href="#'+tabid+'"]';

            document.querySelector(hrefSelector).click();
        }
    };

    // No need.
    const updateAutoCompletionPositions = function() {
        var group = "checkboxgroupautomation";

        if (document.querySelectorAll('input[type=checkbox].'+group) === null || document.querySelectorAll('[data-fieldtype="autocomplete"]') === null) {
            return true;
        }

        document.querySelectorAll('[data-fieldtype="autocomplete"]').forEach((element) => {

            if (element === null) {
                return true;
            }

            var observer = new MutationObserver(function(mutations) {
                mutations.forEach((mutation) => {
                    console.log(mutation);
                    // if(mutation.type === 'attributes') {
                    target = mutation.target;
                    var overrideElement = target.querySelector('.custom-switch');
                    if (overrideElement === null) {
                        return;
                    }
                    overrideElement.parentNode.append(overrideElement);
                    observer.disconnect();
                })
            });
            observer.observe(element, { attributes: true, childList: true, subtree: true, });
            // observer.disconnect();
        })
    }

    const moveOverRidePosition = function() {

        var group = "checkboxgroupautomation";

        if (document.querySelectorAll('input[type=checkbox].'+group) === null) {
            return true;
        }

        document.querySelectorAll('input[type=checkbox].'+group).forEach((overElement) => {
            var id = overElement.id;
            id = id.replace('id_override_', '');
            var element = document.querySelector('div#fitem_id_'+id);
            if (element === null) {
                element = document.querySelector('div#fgroup_id_'+id);
                if (element === null) {
                    return true;
                }
            }

            var parent = overElement.parentNode;
            parent.innerHTML += '<span class="custom-control-label"></span>';

            var nodeToMove = document.createElement('div');
            nodeToMove.classList.add('custom-control', 'custom-switch');
            nodeToMove.append(parent);
            element.querySelector(".felement").append(nodeToMove);
        });
        // Move the override button for autocompletion fields after the autocomplete nodes are created.
        updateAutoCompletionPositions();
    }

    /**
     * Create a modal to display the list of instances which is overriden the template setting.
     *
     * @returns {void}
     */
    const overrideModal = function() {

        // Add the template reference as prefix of the instance reference.
        var templateReference = document.querySelector('#pulse-template-reference');
        var instanceReference = document.querySelector('#fitem_id_insreference .felement');
        if (templateReference && instanceReference) {
            templateReference.classList.remove('hide');
            instanceReference.prepend(templateReference);
        }

        const trigger = document.querySelectorAll('[data-target="overridemodal"]');

        if (trigger === null) {
            return;
        }

        trigger.forEach((elem) => {

            elem.nextSibling.querySelector('.felement').append(elem);

            elem.addEventListener('click', function(e) {
                e.preventDefault();
                var element = e.target;
                var data = element.dataset;
                var instance = document.querySelector('[name=overinstance_'+data.element+']');
                if (instance !== null) {
                    var overrides = JSON.parse(instance.value);
                    overrides.map((value) => {
                        value.url = M.cfg.wwwroot+'/mod/pulse/automation/instances/edit.php?instanceid='+value.id+'&sesskey='+M.cfg.sesskey
                        return value;
                    })
                    Modal.create({
                        title: Str.get_string('instanceoverrides', 'pulse'),
                        body: Template.render('mod_pulse/overrides', {instances: overrides})
                    }).then((modal) => {
                        modal.show();
                    });
                }
            })
        })
    };

    const enableTitleOnSubmit = function() {
        if (document.forms['pulse-automation-template'] === null) {
            return;
        }
        document.forms['pulse-automation-template'].onsubmit = (e) => document.querySelector('[name="title"]').removeAttribute("disabled");
    }

    return {

        init: function() {
            returnToFailedTab();
            overrideModal();
            moveOverRidePosition();
            enableTitleOnSubmit();
        },

        instanceMenuLink: function() {
            var primaryNav = document.querySelector('.secondary-navigation ul.more-nav');
            moveOutMoreMenu(primaryNav);
        },

    }
})
