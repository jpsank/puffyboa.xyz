
function visify(word, idx) {
    word.style.display = "block";
    word.style.color = "rgb(" + word.innerText.length*15 + ",0," + 255*(idx%2) + ")";
}

function update() {
    window.location.hash = "#" + lIdx;

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

window.onload = function() {
    lines = document.getElementsByClassName("line");
    if (lines.length > 0) {
        lines = Array.from(lines).map((l) => [l, l.getElementsByClassName("word")]);
        lIdx = 0;
        wIdx = 0;

        const hash = window.location.hash.substr(1);
        if (hash) {
            lIdx = hash;
        }

        // const file = getUrlVars()["file"];
        // const loc = localStorage.getItem(file);
        // window.addEventListener('beforeunload', function (e) {
        //     localStorage.setItem(file, lIdx);
        // });

        update();
    }
};
