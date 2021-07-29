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
 * Module javascript to place the placeholders.
 * Modified version of IOMAD Email template emailvars.
 *
 * @package   mod_pulse
 * @category  Classes - autoloading
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/fragment'], function(Fragment) {

    return {

        updatecompletionbuttons: function() {
            var instances = document.getElementsByClassName('modtype_pulse');
            var modules = []; var moduleid;
            for (var i = 0; i < instances.length; i++) {
                var instance = instances[i];
                var id = instance.getAttribute('id');
                moduleid = parseInt(id.replace('module-', ''));
                modules.push(moduleid);
            }
            var params = {modules:  JSON.stringify(modules)};
            if (modules.length > 0) {
                let completionbuttons = Fragment.loadFragment('mod_pulse', 'completionbuttons', 1, params);
                var approvebtn, element, referenceNode, completioncontent;
                completionbuttons.then((data) => {
                    data = JSON.parse(data);
                    for (var k in data) {
                        approvebtn = data[k];
                        element = document.getElementById('module-' + k);
                        referenceNode = element.getElementsByClassName('contentwithoutlink')[0];
                        completioncontent = document.createElement('div');
                        completioncontent.innerHTML = approvebtn;
                        completioncontent.classList.add('pulse-completion-btn');
                        referenceNode.parentNode.insertBefore(completioncontent, referenceNode.nextSibling);
                    }
                    return true;
                }).fail();
            }
        },

        init: function() {
            if (document.body.classList.contains('path-course-view')) {
                this.updatecompletionbuttons();
            }
        },

    };
});