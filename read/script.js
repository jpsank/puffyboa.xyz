
function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

var file = getUrlVars()["file"];
var loc = localStorage.getItem(file);
window.scrollTo(0,loc);
window.addEventListener('beforeunload', function (e) {
	var loc = scrollY;
	localStorage.setItem(file, loc);
});

