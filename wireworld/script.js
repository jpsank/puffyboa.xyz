import { PRESETS } from "./presets.js";
import * as wireworld from "./wireworld.js";

function hashChange() {
    let elems = presetSelect.querySelectorAll(".preset");
    for (let e of elems) {
        console.log(e.href.split("#"));
        if (e.href.substr(e.href.indexOf('#')) === window.location.hash) {
            e.style.color = "yellow";
        } else {
            e.style.color = "white";
        }
    }
    
    let hash = window.location.hash.substr(1);

    if (hash in PRESETS) {
        wireworld.init(PRESETS[hash]);
        warningElem.innerHTML = "";
    } else {
        wireworld.init();
        warningElem.innerHTML = "No preset specified";
    }
}

function loop() {
    requestAnimationFrame(loop);

    wireworld.loop();
}

function setup() {
    for (let p in PRESETS) {
        presetSelect.innerHTML += `<a class='preset' href='#${p}'>${p}</a>`;
    }

    wireworld.setup();
    hashChange();
    window.onhashchange = hashChange;


    requestAnimationFrame(loop);
}

const presetSelect = document.getElementById("presetSelect");
const warningElem = document.getElementById("warningElem");

setup();
