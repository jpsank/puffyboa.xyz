
function findGetParameter(parameterName) {
    let result = null;
    let tmp = [];
    location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
            tmp = item.split("=");
            if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
        });
    return result;
}

function visify(word, idx) {
    word.style.display = "block";
    word.style.color = "rgb(" + word.innerText.length*15 + ",0," + 255*(idx%2) + ")";
}

function update() {
    localStorage.setItem(file, lIdx);
    window.scrollTo(0,document.body.scrollHeight);

    let wordsCol = lines[lIdx][1];
    const wordText = wordsCol[wIdx].innerText;

    let timeout = 150+wordText.length*10;
    if (wordText.endsWith(",") || wordText.endsWith(".")) {
        timeout += 50;
    }

    for (let l = 0; l < lIdx; l++) {
        lines[l][0].style.display = "none";
    }
    lines[lIdx][0].style.display = "flex";
    visify(wordsCol[wIdx], wIdx);
    wIdx++;
    if (wIdx >= wordsCol.length) {
        lIdx++;
        wIdx = 0;
        timeout += 200;
    }
    if (lIdx >= lines.length) {
        return;
    }
    setTimeout(update,timeout);
}

let lIdx, wIdx, lines;
const file = findGetParameter("file");

window.onload = function() {
    lines = document.getElementsByClassName("line");
    if (lines.length > 0) {
        document.body.style.overflow = "hidden";

        lines = Array.from(lines).map((l) => [l, l.getElementsByClassName("word")]);
        lIdx = 0;
        wIdx = 0;

        const hash = window.location.hash.substr(1);
        if (hash) {
            lIdx = hash;
        } else {
            let param = localStorage.getItem(file);
            if (param !== null) {
                lIdx = param;
                // window.location.hash = "#" + lIdx;
            }
        }

        // window.onbeforeunload = function () {
        //     localStorage.setItem(file, lIdx);
        // };

        update();
    }
};
