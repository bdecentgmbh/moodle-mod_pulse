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
 * Contain the bulk action for the instance management.
 *
 * @module  mod_pulse/bulkaction
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define("mod_pulse/bulkaction", ["jquery", "core/fragment", "core/modal_factory", "core/modal_events", "core/notification", "core/str", "core/ajax", "core/templates"],
(function($, Fragment, ModalFactory, ModalEvents, notification, String, Ajax, Templates) {

    /**
     * Instance management page selectors.
     */
    const Selectors = {
        table: '#manage-instance-table',
        selectAll: '#manage-instance-tab .selectall-pulse-bulkaction',
        deselectAll: '#manage-instance-tab .deselectall-pulse-bulkaction',
        selectwithoutinsbtn: '#manage-instance-tab #selectwithoutins-btn',
        CheckBoxes: "input[name='bc[]']",
        DeleteBtn: '#manage-instance-tab  #bulkdelete-btn',
        AddBtn: '#manage-instance-tab  #bulkadd-btn',
        EnableBtn: '#manage-instance-tab  #bulkenable-btn',
        DisableBtn: '#manage-instance-tab  #bulkdisable-btn',
        CheckedBoxes: "input[name='bc[]']:checked",
        checkboxgroups: '#manage-instance-tab .bulkaction-group',
        tabUL: '#automation-tabs',
        tabPane: '#pulsetemplates-tab-content .tab-pane',
        tabContent: '#pulsetemplates-tab-content',
        manageInstanceTab: '#manage-instance-tab',
        templateForm: '.mform#pulse-automation-template',
        activeNav: '#automation-tabs .nav-link.active',
    };

    /**
     * Init the bulk select actions in the instance management tab.
     */
    function BulkSelect() {
        const select = document.querySelector(Selectors.selectAll);
        const deselect = document.querySelector(Selectors.deselectAll);
        const tableRoot = () => document.querySelector(Selectors.table);
        const checkboxelm = () => tableRoot().querySelectorAll(Selectors.CheckBoxes);
        const selectwithoutins = document.querySelector(Selectors.selectwithoutinsbtn);
        const checkboxgroup = document.querySelector(Selectors.checkboxgroups);

        // Whent click the select all button on the instance management tab.
        select.addEventListener('click', (e) => {
            checkboxelm().forEach(checkbox => {
                checkbox.checked = true;
                checkboxgroup.classList.remove('hide');
            });
        });

        // Whent click the de-select all button on the instance management tab.
        deselect.addEventListener('click', (e) => {
            checkboxelm().forEach(checkbox => {
                checkbox.checked = false;
                checkboxgroup.classList.add('hide');
            });
        });

        // Whent click the select all without instances button on the instance management tab.
        selectwithoutins.addEventListener('click', (e) => {
            checkboxelm().forEach(checkbox => {
                if (checkbox.classList.contains("emptyinstance")) {
                    checkbox.checked = true;
                    checkboxgroup.classList.remove('hide');
                }
            });
        });

        // Add event listener to checkboxes.
        // checkboxelm().forEach(checkbox => {
        document.addEventListener('change', function(e) {

            if (e.target.matches(Selectors.CheckBoxes)) {
                // Check if at least one checkbox is checked.
                var atLeastOneChecked = Array.from(checkboxelm()).some(function(checkbox) {
                    return checkbox.checked;
                });

                // Toggle visibility of bulk edit action based on checkbox status.
                if (atLeastOneChecked) {
                    checkboxgroup.classList.remove('hide');
                } else {
                    checkboxgroup.classList.add('hide');
                }
            }
        });
    };

    /**
     * Return the selected check boxes coursed ids for the instance management.
     *
     * @returns array $courseids course Ids
     */
    function GetCheckedCourseIDs() {
        var courseids = [];
        var checkedboxes = document.querySelectorAll(Selectors.CheckedBoxes);
        checkedboxes.forEach(checkedbox => {
            courseids.push(checkedbox.value);
        });
        return courseids;
    }

    /**
     * Manage the automation instances confirmation and bulk action in modal.
     *
     * @param {int} params
     */
    function ManageInstances (params) {
        const deletebtn = document.querySelector(Selectors.DeleteBtn);
        const addbtn = document.querySelector(Selectors.AddBtn);
        const disableBtn = document.querySelector(Selectors.DisableBtn);
        const enableBtn = document.querySelector(Selectors.EnableBtn);

        // Click the delete instance bulk action button.
        deletebtn.addEventListener('click', function(e) {
            var courseids = GetCheckedCourseIDs();
            GetInstanceModal(courseids, params, 'delete');
        });

        // Click the add instance bulk action button.
        addbtn.addEventListener('click', function(e) {
            var courseids = GetCheckedCourseIDs();
            GetInstanceModal(courseids, params, 'add');
        });

        // Click the disable instance bulk action button.
        disableBtn.addEventListener('click', function(e) {
            var courseids = GetCheckedCourseIDs();
            GetInstanceModal(courseids, params, 'disable');
        });

        // Click the enable instance bulk action button.
        enableBtn.addEventListener('click', function(e) {
            var courseids = GetCheckedCourseIDs();
            GetInstanceModal(courseids, params, 'enable');
        });

        // Show/hide the instance manage tab of template.
        // Moved the tab outside the form, default tab handlers not works. Used custom method to show hide.
        document.querySelector(Selectors.tabUL).addEventListener('click', function (e) {
            templateInstanceFilter(e);
        });

        const templateInstanceFilter = (e) => {

            document.querySelectorAll(Selectors.tabPane).forEach((e) => {
                e.classList.remove('active');
                e.classList.remove('show')
            });
            // Remove the active.
            var href = (e == null || !e.target.matches('#automation-tabs .nav-link')) ? activeTabHref() : e.target.getAttribute('href');

            document.querySelector(Selectors.tabContent + ' ' + href).classList.add('active');
            document.querySelector(Selectors.tabContent + ' ' + href).classList.add('show');

            // Hide the form.
            if (href == Selectors.manageInstanceTab) {
                document.querySelector(Selectors.templateForm).style.display = 'none';
            } else {
                document.querySelector(Selectors.templateForm).style.display = 'block';
            }
        };

        // Find the active ul.
        const activeTabHref = () => {
            return !document.querySelector(Selectors.activeNav)
                || document.querySelector(Selectors.activeNav).getAttribute('href');
        };

        templateInstanceFilter(null);
    }

    /**
     * Get the instance management confirmation modal.
     *
     * @param {array} courseids Course Ids
     * @param {int} params Templated ID
     * @param {string} action Bulk action name
     */
    function GetInstanceModal(courseids, params, action) {
        var args = {templateid: params, courseids: courseids, action: action};
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: String.get_string('confirmation', 'pulse'),
            body: String.get_string('confirm'+action+'instance', 'pulse'),
            large: false
        })
        .then(function(modal) {

            modal.setButtonText('save', String.get_string('yes'));

            modal.getRoot().on(ModalEvents.save, e => {
                e.preventDefault();
                SubmitFormData(args);
                LoadInstancetable(args);
                modal.getRoot().find('form').submit();
                modal.hide();
            });

            modal.getRoot().on(ModalEvents.hidden, function() {
                modal.destroy();
            });
            modal.show();
            return modal;
        }).catch(notification.exception);
    }

    /**
     * Submit and recieve the message form the modal confirmation on the instance management.
     *
     * @param {string} params
     */
    function SubmitFormData(params) {
        Ajax.call([{
            methodname: 'mod_pulse_delete_instances',
            args: params,
            done: function(response) {
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

    /**
     * Load the current manage instance table to replace the table root.
     *
     * @param {string} params
     */
    function LoadInstancetable(params) {
        var table = document.querySelector(Selectors.table);
        Fragment.loadFragment('mod_pulse', 'get_manageinstance_table', 1, params).done((html, js) => {
            Templates.replaceNode(table, html, js);
        });
    }

    return {
        init: function(params) {
            BulkSelect();
            ManageInstances(params);
        },
    };

}));

//# sourceMappingURL=bulkaction.min.js.map
