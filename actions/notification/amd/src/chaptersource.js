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
 * Frameworks datasource.
 *
 * This module is compatible with core/form-autocomplete.
 *
 * @module     tool_lp/frameworks_datasource
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/modal_factory', 'core/fragment', 'core/str', 'core/modal_events'],
    function($, Ajax, Notification, ModalFactory, Fragment, Str, ModalEvents) {

    const previewModalBody = function(contextID, userid = null) {

        var params;
        if (window.tinyMCE !== undefined && window.tinyMCE.get('id_pulsenotification_headercontent_editor')) {
            // EditorPlugin = window.tinyMCE;
            params = {
                contentheader: window.tinyMCE.get('id_pulsenotification_headercontent_editor').getContent(),
                contentstatic: window.tinyMCE.get('id_pulsenotification_staticcontent_editor').getContent(),
                contentfooter: window.tinyMCE.get('id_pulsenotification_footercontent_editor').getContent(),
                userid: userid
            };
        } else {
            // EditorPlugin = document;
            params = {
                contentheader: document.querySelector('#id_pulsenotification_headercontent_editoreditable').innerHTML,
                contentstatic: document.querySelector('#id_pulsenotification_staticcontent_editoreditable').innerHTML,
                contentfooter: document.querySelector('#id_pulsenotification_footercontent_editoreditable').innerHTML,
                userid: userid
            };
        }

        var dynamicparams = {};

        if (document.querySelector('[name=pulsenotification_dynamiccontent]') !== null) {
            dynamicparams = {
                contentdynamic: document.querySelector('[name=pulsenotification_dynamiccontent]').value,
                contenttype: document.querySelector('[name=pulsenotification_contenttype]').value,
                chapterid: document.querySelector('[name=pulsenotification_chapterid]').value,
                contentlength: document.querySelector('[name=pulsenotification_contentlength]').value,
            };
        }
        // Get the form data.
        var formData;
        var form = document.forms['pulse-automation-template'];
        var formdata = new FormData(form);
        formdata = new URLSearchParams(formdata).toString();
        formData = {
            formdata: formdata
        };

        var finalParams = {...params, ...dynamicparams, ...formData};

        return Fragment.loadFragment('pulseaction_notification', 'preview_content', contextID, finalParams);
    };

    const previewModal = function(contextID) {

        ModalFactory.create({
            title: Str.get_string('preview', 'pulseaction_notification'),
            body: previewModalBody(contextID),
            large: true,
        }).then((modal) => {
            modal.show();

            modal.getRoot().on(ModalEvents.bodyRendered, function() {
                modal.getRoot().get(0).querySelector('[name=userselector]').addEventListener('change', (e) => {
                    e.preventDefault();
                    var target = e.target;
                    modal.setBody(previewModalBody(contextID, target.value));
                });
            });

            return;
        }).catch();
    };

    const notificationModal = function(contextID, instance, userid) {

        var params = {
            instanceid: instance,
            userid: userid
        };

        ModalFactory.create({
            title: Str.get_string('preview', 'pulseaction_notification'),
            body: Fragment.loadFragment('pulseaction_notification', 'preview_instance_content', contextID, params),
            large: true,
        }).then((modal) => {
            modal.show();
            return;
        }).catch();
    };

    return {

        processResults: function(selector, modules) {
            return modules;
        },

        transport: function(selector, query, success, failure) {

            var mod = document.querySelector("#id_pulsenotification_dynamiccontent");

            var promise = Ajax.call([{
                methodname: 'pulseaction_notification_get_chapters',
                args: {mod: mod.value}
            }]);

            promise[0].then(function(result) {
                success(result);
                return;
            }).fail(failure);
        },

        updateChapter: function() {

            const SELECTORS = {
                chaperType: "#id_pulsenotification_contenttype",
                mod: "#id_pulsenotification_dynamiccontent"
            };

            document.querySelector(SELECTORS.chaperType).addEventListener("change", () => resetChapter());
            document.querySelector(SELECTORS.mod).addEventListener("change", () => resetChapter());
            var chapter = document.querySelector("#id_pulsenotification_chapterid");

            /**
             *
             */
            function resetChapter() {
                chapter.innerHTML = '';
                chapter.value = '';
                var event = new Event('change');
                chapter.dispatchEvent(event);
            }
        },

        previewNotification: function(contextid) {
            var btn = document.querySelector('[name="pulsenotification_preview"]');

            if (btn === null) {
                return;
            }

            btn.addEventListener('click', function() {
                previewModal(contextid);
            });
        },

        reportModal: function(contextID) {
            // View content.
            var btn = document.querySelectorAll('[data-target="view-content"]');

            if (btn === null) {
                return;
            }

            btn.forEach((element) => {
                element.addEventListener('click', function(e) {

                    var target = e.target.closest('a');

                    var instance = target.dataset.instanceid;
                    var userid = target.dataset.userid;

                    notificationModal(contextID, instance, userid); // Notification modal.
                });
            });
        }
    };

});
