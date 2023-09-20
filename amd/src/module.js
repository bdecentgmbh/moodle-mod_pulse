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
 * @module   mod_pulse/module
 * @category  Classes - autoloading
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core_editor/events'], function(events) {

    var tinyEditor = false;

    return {
        /**
         * Setup the classes to editors works with placeholders.
         */
        init: function() {
            var module = this;

            var templatevars = document.getElementsByClassName("fitem_id_templatevars_editor");
            for (var l = 0; l < templatevars.length; l++) {
                templatevars[l].addEventListener('click', function() {
                    var EditorInput = document.getElementById('id_pulse_content_editoreditable');
                    module.insertCaretActive(EditorInput);
                });
            }

            var notificationheader = document.getElementById('admin-notificationheader');
            if (notificationheader !== null) {
                notificationheader.addEventListener('click', function() {
                    var EditorInput = document.getElementById('id_s_mod_pulse_notificationheadereditable');
                    module.insertCaretActive(EditorInput);
                });
            }

            var notificationfooter = document.getElementById('admin-notificationfooter');
            if (notificationfooter !== null) {
                notificationfooter.addEventListener('click', function() {
                    var EditorInput = document.getElementById('id_s_mod_pulse_notificationfootereditable');
                    module.insertCaretActive(EditorInput);
                });
            }

            var templatevars = document.getElementsByClassName("fitem_id_templatevars_editor");
            if (templatevars) {
                templatevars.forEach((elem) => {
                    elem.addEventListener('click', function(e) {
                        var target = e.currentTarget;
                        var EditorInput  = target.querySelector('[id*="_editoreditable"]');
                        module.insertCaretActive(EditorInput);
                    });
                })
            }

            // console.log(window.tinyMCE.get());
            var targetNode = document.querySelector('textarea[id$=_editor]');
            if (targetNode !== null) {
                var observer = new MutationObserver(function() {
                    if (targetNode.style.display == 'none') {
                        setTimeout(initIframeListeners, 100);
                    }
                });
                observer.observe(targetNode, { attributes: true, childList: true });
            }

            const initIframeListeners = () => {

                var iframes = document.querySelectorAll('[data-fieldtype="editor"] iframe');
                if (iframes === null || !iframes.length) {
                    return false;
                }

                iframes.forEach((iframe) => {
                    iframe.contentDocument.addEventListener('click', function(e) {

                        var currentFrame = e.target;
                        iframes.forEach((frame) => {
                            var frameElem = frame.contentDocument.querySelector(".insertatcaretactive");
                            if (frameElem != null) {
                                frameElem.classList.remove("insertatcaretactive");
                            }
                        });

                        var contentBody = currentFrame.querySelector('body');
                        if (contentBody !== null) {
                            contentBody.classList.add("insertatcaretactive");
                            var id = contentBody.dataset.id;
                            var editor = window.tinyMCE.get(id);
                            tinyEditor = editor;
                        }
                    });
                });
            }


            var clickforword = document.getElementsByClassName('clickforword');
            for (var i = 0; i < clickforword.length; i++) {
                clickforword[i].addEventListener('click', function(e) {
                    e.preventDefault(); // To prevent the default behaviour of a tag.

                    var content = "{" + this.getAttribute('data-text') + "}";
                    if (tinyEditor) {
                        tinyEditor.selection.setContent(content);
                    } else {
                        module.insertAtCaret(content);
                    }
                });
            }

            // Make selected roles as badges in module edit form page.
            if (document.getElementById('page-mod-pulse-mod') !== null && document.getElementById('page-mod-pulse-mod')
                .querySelector("#fgroup_id_completionrequireapproval [data-fieldtype='autocomplete']") !== null) {
                const textNodes = this.getAllTextNodes(
                    document.getElementById('page-mod-pulse-mod')
                    .querySelector("#fgroup_id_completionrequireapproval [data-fieldtype='autocomplete']")
                );
                textNodes.forEach(node => {
                    const span = document.createElement('span');
                    span.classList = 'badge badge-info pulse-completion-roles';
                    node.after(span);
                    span.appendChild(node);
                });
            }
        },

        insertCaretActive: function(EditorInput) {
            if (EditorInput === null) {
                return;
            }
            tinyEditor = false;
            var caret = document.getElementsByClassName("insertatcaretactive");
            for (var j = 0; j < caret.length; j++) {
                caret[j].classList.remove("insertatcaretactive");
            }
            EditorInput.classList.add("insertatcaretactive");
        },

        /**
         * Filter text from node.
         * @param  {string} element
         * @returns {array} list of childNodes.
         */
        getAllTextNodes: function(element) {
            return Array.from(element.childNodes)
            .filter(node => node.nodeType === 3 && node.textContent.trim().length > 1);
        },

        /**
         * Insert the placeholder in selected caret place.
         * @param  {string} myValue
         */
        insertAtCaret: function(myValue) {
            var caretelements = document.getElementsByClassName("insertatcaretactive");
            var sel, range;
            for (var n = 0; n < caretelements.length; n++) {
                var thiselem = caretelements[n];

                if (typeof thiselem.value === 'undefined' && window.getSelection) {
                    sel = window.getSelection();
                    if (sel.getRangeAt && sel.rangeCount) {
                        range = sel.getRangeAt(0);
                        range.deleteContents();
                        range.insertNode(document.createTextNode(myValue));

                        for (let position = 0; position != (myValue.length + 1); position++) {
                            sel.modify("move", "right", "character");
                        }
                    }
                } else if (typeof thiselem.value === 'undefined' && document.selection && document.selection.createRange) {
                    range = document.selection.createRange();
                    range.text = myValue;
                }

                if (typeof thiselem.value !== 'undefined') {
                    if (document.selection) {
                        // For browsers like Internet Explorer.
                        thiselem.focus();
                        sel = document.selection.createRange();
                        sel.text = myValue;
                        thiselem.focus();
                    } else if (thiselem.selectionStart || thiselem.selectionStart == '0') {
                        // For browsers like Firefox and Webkit based.
                        var startPos = thiselem.selectionStart;
                        var endPos = thiselem.selectionEnd;
                        thiselem.value = thiselem.value.substring(0, startPos)
                            + myValue + thiselem.value.substring(endPos, thiselem.value.length);
                        thiselem.focus();
                        thiselem.selectionStart = startPos + myValue.length;
                        thiselem.selectionEnd = startPos + myValue.length;
                        thiselem.focus();
                    } else {
                        thiselem.value += myValue;
                        thiselem.focus();
                    }
                }
            }
        },
    };
});
