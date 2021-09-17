define(['jquery', 'core/modal_factory', 'mod_pulse/modal_preset', 'mod_pulse/events', 'core/str',
'core/fragment', 'core/ajax', 'core/templates', 'core/loadingicon', 'core/notification'],
    function($, Modal, ModalPreset, PresetEvents, Str, Fragment, AJAX, Templates, Loadingicon, Notification) {


    var Preset = function(contextId, courseid, pageparams) {
        this.contextId = contextId;
        this.courseid = courseid;
        this.pageparams = pageparams;

        this.setupmodal();
    };

    Preset.prototype.contextId = 0;

    Preset.prototype.courseid = 0;

    Preset.prototype.pageparams = [];

    Preset.prototype.loadIconElement = '.modal-footer #loader-icon';

    Preset.prototype.actionbuttons = '.modal-footer button';

    Preset.prototype.setupmodal = function() {

        var THIS = this;

        var triggerelement = document.querySelectorAll('.pulse-usepreset');
        triggerelement.forEach((element) => element.addEventListener('click', () => {
            var presetid = element.getAttribute('data-presetid');
            var params = {'presetid': presetid, 'courseid': THIS.courseid};
            Modal.create({
                type: ModalPreset.TYPE,
                body: Fragment.loadFragment('mod_pulse', 'get_preset_preview', THIS.contextId, params),
                large: true
            }).then(modal => {
                modal.show();
                modal.getRoot().on(PresetEvents.customize, () => {
                    var modform = document.forms[0];
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

    Preset.prototype.applyCustomize = function(params, contextID, modal) {
        Fragment.loadFragment('mod_pulse', 'apply_preset', contextID, params).done((html, js) => {
            this.handleFormSubmissionResponse(html, js);
            modal.destroy();
        });
    };

    Preset.prototype.disableButtons = function() {
        var buttons = document.querySelectorAll(this.actionbuttons);
        for (let $i in buttons) {
            buttons[$i].disabled = true;
        }
    };

    Preset.prototype.handleFormSubmissionResponse = (data, js) => {
        var newform = document.createElement('div');
        newform.innerHTML = data;
        Templates.replaceNode('[action="modedit.php"]', data, js);
    };

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


    return {
        init: (contextId, courseid) => {
            new Preset(contextId, courseid);
        }
    };
});
