define(['jquery', 'core/modal_factory', 'mod_pulse/modal_preset', 'mod_pulse/events', 'core/str',
'core/fragment', 'core/ajax', 'core/templates', 'core/loadingicon', 'core/notification', 'core/modal_events'],
    function($, Modal, ModalPreset, PresetEvents, Str, Fragment, AJAX, Templates, Loadingicon, Notification, ModalEvents) {

    var SELECTOR = {
        presetAvailability: '.preset-config-params .availability-field'
    };

    /**
     * Preset module declaration. Setup the global values.
     * @param  {int} contextId
     * @param  {int} courseid
     * @param  {int} section
     */
    var Preset = function(contextId, courseid, section) {
        this.contextId = contextId;
        this.courseid = courseid;
        this.section = section;
        this.loadPresetsList();
    };

    Preset.prototype.listElement = {'selector': 'pulse-presets-data', "loaded": "data-listloaded"};

    Preset.prototype.contextId = 0;

    Preset.prototype.courseid = 0;

    Preset.prototype.section = 0;

    Preset.prototype.pageparams = [];

    Preset.prototype.loadIconElement = '.modal-footer #loader-icon';

    Preset.prototype.actionbuttons = '.modal-footer button';

    /**
     * Setup the presets modal event listeners.
     */
    Preset.prototype.setupmodal = function() {

        var THIS = this;

        var triggerelement = document.querySelectorAll('.pulse-usepreset');
        // Modal attachment point.
        var attachmentPoint = document.createElement('div');
        attachmentPoint.classList.add('modal-preset');
        triggerelement.forEach((element) => element.addEventListener('click', () => {
            var presetid = element.getAttribute('data-presetid');
            var presettitle = element.getAttribute('data-presettitle');
            var params = {'presetid': presetid, 'courseid': THIS.courseid, 'section': THIS.section};

            document.body.prepend(attachmentPoint);
            Modal.create({
                type: ModalPreset.TYPE,
                title: Str.get_string('presetmodaltitle', 'pulse', {'title': presettitle}),
                body: Fragment.loadFragment('mod_pulse', 'get_preset_preview', THIS.contextId, params),
                large: true,
            }).then(modal => {
                // Make the modal attachment point to overcome the restriction access condition.
                modal.attachmentPoint = attachmentPoint;
                modal.show();
                modal.getRoot().on(ModalEvents.bodyRendered, function() {
                    THIS.reinitAvailability(SELECTOR.presetAvailability);
                    THIS.fieldChangedEvent();
                });
                // Destroy the modal on hidden to reload the editors.
                modal.getRoot().on(ModalEvents.hidden, function() {
                    modal.destroy.bind(modal);
                    THIS.reinitAvailability();
                });

                // Apply and customize method.
                modal.getRoot().on(PresetEvents.customize, () => {
                    var modform = document.querySelector('#mod-pulse-form');
                    var modformdata = new FormData(modform);
                    modal.getRoot().get(0).querySelectorAll('form').forEach(form => {
                        var formdata = new FormData(form);
                        formdata = new URLSearchParams(formdata).toString();
                        var pageparams = new URLSearchParams(modformdata).toString();
                        params = {formdata: formdata, pageparams: pageparams};

                        Loadingicon.addIconToContainer(this.loadIconElement);
                        THIS.disableButtons();
                        THIS.applyCustomize(params, THIS.contextId, modal);
                    });
                });
                // Apply and save method.
                modal.getRoot().on(PresetEvents.save, (e) => {
                    e.preventDefault();
                    Loadingicon.addIconToContainer(this.loadIconElement);
                    THIS.disableButtons();
                    var formdata = {};
                    modal.getRoot().get(0).querySelectorAll('form').forEach(form => {
                        formdata = new FormData(form);
                        this.restorePreset(formdata, THIS.contextId);
                    });
                });
                return true;
            }).catch(Notification.exception);
        }));
    };


    Preset.prototype.fieldChangedEvent = () => {
        var confParam = document.getElementById("preset-configurable-params");
        var reminders = ['first', 'second', 'recurring'];
        var methods = ['fixed', 'relative'];
        var fieldName, changeinput, id, changeName, split;
        confParam.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('change', (event) => {
                fieldName = event.target.getAttribute('name');
                if (confParam.querySelector('input[name="' + fieldName + '_changed"]') !== null) {
                    confParam.querySelector('input[name="' + fieldName + '_changed"]').value = true;
                }
            });
        });

        reminders.forEach(reminder => {
            confParam.querySelectorAll('[name="' + reminder + '_schedule"').forEach(schedule => {
                schedule.addEventListener('change', (e) => {
                    changeName = e.target.getAttribute('name');
                    changeinput = 'input[name="' + changeName + '_arr_changed"]';
                    confParam.querySelector(changeinput).value = true;
                });
            });
            methods.forEach(method => {
                id = reminder + "_" + method + "date";
                confParam.querySelectorAll('[name*="' + id + '"]').forEach(opt => {
                    opt.addEventListener('change', (e) => {
                        split = e.target.getAttribute('name').split('[');
                        changeName = (split.hasOwnProperty(1)) ? split[0] : split;
                        changeinput = 'input[name="' + changeName + '_changed"]';
                        confParam.querySelector(changeinput).value = true;
                    });
                });
            });
        });
    };

    /**
     * Reinitialize the availability javascript.
     * @param {string} selector
     */
    Preset.prototype.reinitAvailability = function(selector = '.availability-field') {
        if (typeof M.core_availability.form !== "undefined") {
            this.resetRestrictPlugins();
            document.querySelectorAll(selector).forEach((field) => field.parentNode.removeChild(field));
            M.core_availability.form.init();
        }
    };

    Preset.prototype.resetRestrictPlugins = function() {
        if (typeof M.core_availability.form !== "undefined" && document.getElementById('id_availabilityconditionsjson') !== null) {
            M.core_availability.form.restrictByGroup = null;
            var availabilityPlugins = (typeof M.core_availability.form.plugins !== 'undefined')
                ? M.core_availability.form.plugins : {};
            var plugin = '';
            for (var i in availabilityPlugins) {
                plugin = "availability_" + i;
                if (M.hasOwnProperty(plugin)) {
                    M[plugin].form.addedEvents = false;
                }
            }
        }
    };

    /**
     * Apply and customize triggered using fragment. Response will replaced with current mod form.
     * @param  {string} params
     * @param  {int} contextID
     * @param  {object} modal
     */
    Preset.prototype.applyCustomize = function(params, contextID, modal) {
        Fragment.loadFragment('mod_pulse', 'apply_preset', contextID, params).done((html, js) => {
            modal.destroy();
            // Reset the availability to work for upcoming response html.
            this.resetRestrictPlugins();
            this.handleFormSubmissionResponse(html, js);
        });
    };

    /**
     * Disable the modal save and customize buttons to prevent reinit.
     */
    Preset.prototype.disableButtons = function() {
        var buttons = document.querySelectorAll(this.actionbuttons);
        for (let $i in buttons) {
            buttons[$i].disabled = true;
        }
    };

    /**
     * Handle the loaded fragment output of customize method pulse mod.
     * @param  {html} data
     * @param  {string} js
     */
    Preset.prototype.handleFormSubmissionResponse = (data, js) => {
        var newform = document.createElement('div');
        newform.innerHTML = data;
        Templates.replaceNode('[action="modedit.php"]', data, js);

    };

    /**
     * Initiate the apply and save method to create the pulse module with custom daa.
     * @param  {FormData} formdata
     * @param  {int} contextid
     */
    Preset.prototype.restorePreset = (formdata, contextid) => {
        var formdatastr = new URLSearchParams(formdata).toString();
        var promises = AJAX.call([{
            methodname: 'mod_pulse_apply_presets',
            args: {contextid: contextid, formdata: formdatastr}
        }]);

        promises[0].done((response) => {
            response = JSON.parse(response);
            if (typeof response.url != 'undefined') {
                window.location.href = response.url;
            }
        });
    };

    /**
     * Load list of available presets.
     */
    Preset.prototype.loadPresetsList = function() {
        var listParent = document.getElementById(this.listElement.selector);

        if (listParent !== null) {
            if (listParent.getAttribute(this.listElement.loaded) == 'false') {
                Fragment.loadFragment('mod_pulse', 'get_presetslist', this.contextId, {'courseid': this.courseid})
                .done((html, js) => {
                    Templates.replaceNodeContents(listParent, html, js);
                    listParent.setAttribute(this.listElement.loaded, 'true');
                    this.setupmodal();
                });
            }
        }
    };

    return {
        init: (contextId, courseid, section) => {
            new Preset(contextId, courseid, section);
        }
    };
});
