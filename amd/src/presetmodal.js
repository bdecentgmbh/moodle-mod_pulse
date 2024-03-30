import Modal from 'core/modal';
import * as CustomEvents from 'core/custom_interaction_events';
import * as PresetEvents from 'mod_pulse/events';

const SELECTORS = {
    SAVE_BUTTON: '[data-action="save"]',
    CUSTOMIZE_BUTTON: '[data-action="customize"]',
    CANCEL_BUTTON: '[data-action="cancel"]',
};

export default class PresetModal extends Modal {

    static TYPE = 'PulsePresetModal';
    static TEMPLATE = 'mod_pulse/modal_preset';

    registerEventListeners() {
        // Apply parent event listeners.
        super.registerEventListeners(this);

        this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function (event, data) {
            // Load the backupfile.
            document.querySelectorAll('.preset-config-params form.mform').forEach(form => {
                form.importmethod.value = 'save';
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                });
            });

            if (document.querySelectorAll('.preset-config-params [data-fieldtype="submit"] input').length != 0) {
                document.querySelectorAll('.preset-config-params [data-fieldtype="submit"] input')[0].click();
            }

            var approveEvent = $.Event(PresetEvents.save);
            this.getRoot().trigger(approveEvent, this);

            if (!approveEvent.isDefaultPrevented()) {
                this.destroy();
                data.originalEvent.preventDefault();
            }
            event.preventDefault();
        }.bind(this));


        this.getModal().on(CustomEvents.events.activate, SELECTORS.CUSTOMIZE_BUTTON, function (event, data) {
            // Add your logic for when the login button is clicked. This could include the form validation,
            document.querySelectorAll('.preset-config-params form.mform').forEach(form => {
                form.importmethod.value = 'customize';
            });

            var customizeEvent = $.Event(PresetEvents.customize);
            this.getRoot().trigger(customizeEvent, this);

            if (!customizeEvent.isDefaultPrevented()) {
                data.originalEvent.preventDefault();
            }
            event.preventDefault();

        }.bind(this));

        this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function () {
            this.destroy();
        }.bind(this));
    };
}

PresetModal.registerModalType();
