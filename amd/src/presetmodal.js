// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Modal for preset selection.
 *
 * @module  mod_pulse/presetmodal
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import * as CustomEvents from 'core/custom_interaction_events';
import * as PresetEvents from 'mod_pulse/events';
import $ from 'jquery';

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

        this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function(event, data) {
            // Load the backupfile.
            document.querySelectorAll('.preset-config-params form.mform').forEach(form => {
                form.importmethod.value = 'save';
                form.addEventListener('submit', function(e) {
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
    }
}

if (typeof PresetModal.registerModalType !== 'undefined') {
    PresetModal.registerModalType();
}
