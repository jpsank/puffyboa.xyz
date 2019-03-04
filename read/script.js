
function getUrlVars() {
    const vars = {};
    window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

const file = getUrlVars()["file"];
const loc = localStorage.getItem(file);
window.scrollTo(0,loc);
window.addEventListener('beforeunload', function (e) {
	localStorage.setItem(file, scrollY);
});

