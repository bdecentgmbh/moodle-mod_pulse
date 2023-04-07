define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_registry', 'mod_pulse/events'],
        function($, Notification, CustomEvents, Modal, ModalRegistry, PresetEvents) {

    var registered = false;
    var SELECTORS = {
        SAVE_BUTTON: '[data-action="save"]',
        CUSTOMIZE_BUTTON: '[data-action="customize"]',
        CANCEL_BUTTON: '[data-action="cancel"]',
    };

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalPreset = function(root) {
        Modal.call(this, root);

        if (!this.getFooter().find(SELECTORS.SAVE_BUTTON).length) {
            Notification.exception({message: 'No "Apply and save" button found'});
        }

        if (!this.getFooter().find(SELECTORS.CUSTOMIZE_BUTTON).length) {
            Notification.exception({message: 'No "Apply and customize" button found'});
        }

        if (!this.getFooter().find(SELECTORS.CANCEL_BUTTON).length) {
            Notification.exception({message: 'No cancel button found'});
        }
    };

    ModalPreset.TYPE = 'PresetModal';
    ModalPreset.prototype = Object.create(Modal.prototype);
    ModalPreset.prototype.constructor = ModalPreset;
    ModalPreset.prototype.formData = '';

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalPreset.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);

        this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function(event, data) {
            // Load the backupfile.
            document.querySelectorAll('.preset-config-params form.mform').forEach(form => {
                form.importmethod.value = 'save';
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                });
            });
            document.querySelectorAll('.preset-config-params [data-fieldtype="submit"] input')[0].click();

            var approveEvent = $.Event(PresetEvents.save);
            this.getRoot().trigger(approveEvent, this);

            if (!approveEvent.isDefaultPrevented()) {
                this.destroy();
                data.originalEvent.preventDefault();
            }
            event.preventDefault();
        }.bind(this));


        this.getModal().on(CustomEvents.events.activate, SELECTORS.CUSTOMIZE_BUTTON, function(event, data) {
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

        this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function() {
            this.destroy();
        }.bind(this));
    };

    // Automatically register with the modal registry the first time this module is imported so that you can create modals
    // of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(ModalPreset.TYPE, ModalPreset, 'mod_pulse/modal_preset');
        registered = true;
    }

    return ModalPreset;
});
