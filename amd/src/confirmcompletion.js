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
 * Manual completion confirmation options.
 *
 * @module   mod_pulse/confirmcompletion
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", 'core/str', "core/modal_factory", 'core/notification', 'core/ajax', "core/fragment", 'core/modal_events'],
    (function ($, Str, ModalFactory, notification, Ajax, Fragment, ModalEvents) {

        /**
         * Show mark as completion button confirmation modal.
         * @param {init} contextid
         */
        const ButtonConfirmation = function (contextid) {
            if (document.body.classList.contains('path-course-view')) {
                var buttons = document.querySelectorAll('.pulse-user-manualcompletion-btn');
                buttons.forEach(function (element) {
                    element.addEventListener('click', function (e) {
                        var classList = e.target.className;
                        var id = classList.match(/confirmation-(\d+)/);
                        if (id) {
                            id = id[1];
                            getModal(id, contextid);
                        }
                    });
                });
            }
        };

        /**
         * Get the activity completion confirmation modal.
         *
         * @param {array} id instance id
         * @param {int} contextid Context ID
         */
        const getModal = function (id, contextid) {
            var args = { id: id };

            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: Str.get_string('confirmation', 'pulse'),
                body: '',
                large: false
            }).then(function (modal) {
                modal.show();

                Fragment.loadFragment('mod_pulse', 'get_confirmation_content', contextid, args).then(function (html) {
                    modal.setBody(html);
                    return html;
                }).catch(notification.exception);

                modal.setButtonText('save', Str.get_string('yes'));

                modal.getRoot().on(ModalEvents.save, function (e) {
                    e.preventDefault();
                    submitformdata(args);
                    modal.getRoot().find('form').submit();
                    modal.hide();
                });

                modal.getRoot().on(ModalEvents.hidden, function () {
                    modal.destroy();
                });

                return modal;
            }).catch(notification.exception);
        };

        /**
         * Submit and recieve the message form the modal confirmation on the activity completion.
         *
         * @param {string} params
         */
        const submitformdata = function (params) {
            Ajax.call([{
                methodname: 'mod_pulse_manual_completion',
                args: params,
                done: function (response) {
                    window.location.reload();
                    if (response.message) {
                        notification.addNotification({
                            message: response.message,
                            type: "success"
                        });
                    }
                }
            }]);
        };

        return {
            init: function (contextid) {
                ButtonConfirmation(contextid);
            },
        };
    }));
