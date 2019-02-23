import * as conway from "./conway.js";
import * as fidget from "./fidget.js";


function resize() {
    conway.resize();
    fidget.resize();
}

function loop() {
    requestAnimationFrame(loop);
    conway.loop();
    fidget.loop();
}

function setup() {
    addEventListener("resize", resize);
    conway.setup();
    fidget.setup();
    requestAnimationFrame(loop);
}

window.onload = setup;

