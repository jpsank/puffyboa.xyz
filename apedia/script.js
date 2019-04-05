
function focusOut(e) {
    let textAreas = e.getElementsByTagName("textarea");
    for (const ta of textAreas) {
        if (ta.value !== "") {
            return;
        }
    }
    e.classList.remove("activated");
}

function focusIn(e) {
    e.classList.add("activated");
}

