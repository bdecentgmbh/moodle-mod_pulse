require(['core/fragment'], function(Fragment) {

    function init() {
        var instances = document.getElementsByClassName('modtype_pulse');
        console.log(instances);
        var modules = [];
        for (var i=0; i < instances.length; i++) {
            var instance = instances[i];
            var id = instance.getAttribute('id');
            moduleid = parseInt(id.replace('module-', ''));
            modules.push(moduleid);
        }
        var params = {modules:  JSON.stringify(modules)};
        if (modules.length > 0) {
            let completionbuttons = Fragment.loadFragment('mod_pulse', 'completionbuttons', 1, params);
            completionbuttons.then((data) => {
                data = JSON.parse(data);
                for( var k in data) {
                approvebtn = data[k];
                console.log(k);
                element = document.getElementById('module-'+k);
                referenceNode = element.getElementsByClassName('contentwithoutlink')[0]
                completioncontent = document.createElement('div');
                completioncontent.innerHTML = approvebtn;
                completioncontent.classList.add('pulse-completion-btn');
                referenceNode.parentNode.insertBefore(completioncontent, referenceNode.nextSibling);
                // actions.innerHTML = approvebtn+actions.innerHTML;
                console.log(completioncontent);
                }
            })
        }
    }

    if (document.body.classList.contains('path-course-view') ) {
        init();
    }

});