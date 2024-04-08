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
 * vars javascript to place the placeholders.
 * Modified version of IOMAD Email template emailvars.
 *
 * @module   mod_pulse/vars
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define("mod_pulse/vars", ["jquery", 'core/str'], (function($, Str) {

    /**
     * Toggle the placeholder container hide/show.
     */
    const initPlaceholderToggle = function() {

        var selector = ".mod-pulse-emailvars-toggle";

        var emailvars = document.querySelectorAll(selector);

        if (emailvars === undefined || emailvars === null) {
            return;
        }

        emailvars.forEach((editor) => {
            editor.querySelector('.email-vars-button').addEventListener('click', (e) => {
                e.preventDefault();

                var target = e.target.closest('a');
                var btnurl = target.getAttribute('href'); // Get the clicked placeholder element vars content selector.
                var toggleIcon = target.querySelector('i');
                var varsContent = document.querySelector(btnurl); // Email vars content body.

                varsContent.classList.toggle('show');

                // Change the toggle icon direction.
                toggleIcon.classList.toggle('fa-angle-double-up');
                toggleIcon.classList.toggle('fa-angle-double-down');
            });
        });
    };

    /**
     * Email vars placeholders show more / show less.
     */
    const initVarsExpand = function() {
        var selector = '.mod-pulse-emailvars-toggle .pulse-email-placeholders li .button-show-more';

        var placeholders = document.querySelectorAll(selector);

        if (placeholders === undefined || placeholders === null) {
            return;
        }

        placeholders.forEach((showmorebtn) => {
            showmorebtn.addEventListener('click', (e) => {
                e.preventDefault();
                var target = e.target.closest('a');
                var placeholderurl = target.getAttribute('href'); // Get the clicked show more element placeholder content selector.
                var placeholderContent = document.querySelector(placeholderurl); // Placeholder content body.
                placeholderContent.classList.toggle('less');

                if (target.innerHTML == 'Show more') {
                    var showless = Str.get_string('showless', 'mod_pulse');
                    showless.done(function(localizedShowlessString) {
                        target.innerHTML = localizedShowlessString;
                    });
                } else {
                    var showmore = Str.get_string('showmore', 'mod_pulse');
                    showmore.done(function(localizedShowmoreString) {
                        target.innerHTML = localizedShowmoreString;
                    });
                }
            });
        });
    };

    return {

        init: function() {
            initPlaceholderToggle();
            initVarsExpand();
        },
    };

}));
