define('pulseaction_notification/notification', ['jquery', 'core/fragment'], function($, Fragment) {

    var contextID;

    const SELECTORS = {
        chaperType : "#id_pulsenotification_contenttype",
        mod: "#id_pulsenotification_dynamiccontent"
    };

    const addChapterEventListners = (contextid) => {

        contextID = contextid

        document.querySelector(SELECTORS.chaperType).addEventListener("change", (e) => updateChapter());
        document.querySelector(SELECTORS.mod).addEventListener("change", (e) => updateChapter());
    }

    const updateChapter = (method) => {
        var type = document.querySelector("#id_pulsenotification_contenttype").value;
        var mod = document.querySelector("#id_pulsenotification_dynamiccontent");
        var chapter = document.querySelector("#id_pulsenotification_chapter");

        if (parseInt(type) != 2) {
            return true;
        }

        var params = {mod: mod.value};

        // TODO: Loading icon near.
        Fragment.loadFragment('pulseaction_notification', 'update_chapters', contextID, params).then((html, js) => {
            chapter.innerHTML = html;
        }).catch();
    }


    return {

        init: function() {

        },

        updateChapter: function(contextid) {
            addChapterEventListners(contextid);
        }
    }
})
