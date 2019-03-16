
class WireWorld {
    constructor(rows,cols) {
        this.array = [];
        for (let i=0; i<rows; i++) {
            this.array[i] = new Array(cols).fill(0);
        }
    }
    getNeighbors(r, c, type) {
        const offsets = [[1,1],[1,0],[1,-1],[0,1],[0,-1],[-1,1],[-1,0],[-1,-1]];
        const neighs = [];
        for (const o of offsets) {
            let r2 = (r+o[0]) % this.array.length;
            if (r2 < 0) {r2 += this.array.length;}
            let c2 = (c+o[1]) % this.array[r2].length;
            if (c2 < 0) {c2 += this.array[r2].length;}
            if (this.array[r2][c2] === type) {
                neighs.push([r2,c2]);
            }
        }
        return neighs;
    }
    getTmpArray() {
        let newArray = [];
        for (let i = 0; i < this.array.length; i++) {
            newArray[i] = this.array[i].slice();
        }
        return newArray;
    }
    step() {
        const tmpArray = this.getTmpArray();
        for (let r=0; r < this.array.length; r++) {
            for (let c=0; c < this.array[r].length; c++) {
                switch (this.array[r][c]) {
                    case 0:
                        break;
                    case 1:
                        tmpArray[r][c] = 2;
                        break;
                    case 2:
                        tmpArray[r][c] = 3;
                        break;
                    case 3:
                        const heads = this.getNeighbors(r,c, 1);  // neighbors that are electron heads
                        if (heads.length === 1 || heads.length === 2) {
                            tmpArray[r][c] = 1;
                        }
                        break;
                }
            }
        }
        this.array = tmpArray;
    }
    set(points, type) {
        for (const [r,c] of points) {
            this.array[r][c] = type;
        }
    }
    preset(preset, origin) {
        for (let r=0; r < preset.length; r++) {
            for (let c=0; c < preset[r].length; c++) {
                this.array[origin[0]+r][origin[1]+c] = preset[r][c];
            }
        }
    }
}


function mouseDown(e) {
    if (e.target === canvas) {
        drag = true;
        mouseMove(e);
    }
}
function mouseUp(e) {
    drag = false;
}
function mouseOut(e) {
    drag = false;
}
function mouseMove(e) {
    if (e.target === canvas) {
        if (drag) {
            let mouseX = e.offsetX;
            let mouseY = e.offsetY;

            const tileSizeX = canvas.width / game.array[0].length;
            const tileSizeY = canvas.height / game.array.length;

            let tileX = Math.floor(mouseX / tileSizeX);
            let tileY = Math.floor(mouseY / tileSizeY);

            game.set([[tileY, tileX]], 3)
        }
    }
}
function getTouchPos(canvasDom, touchEvent) {
    const rect = canvasDom.getBoundingClientRect();
    return {
        x: touchEvent.touches[0].clientX - rect.left,
        y: touchEvent.touches[0].clientY - rect.top
    };
}
function touchStart(e) {
    if (e.target === canvas) {
        e.preventDefault();
        drag = true;
        touchMove(e);
    }
}
function touchEnd(e) {
    if (e.target === canvas) {
        e.preventDefault();
    }
    drag = false;
}
function touchMove(e) {
    if (e.target === canvas) {
        e.preventDefault();
        const touchPos = getTouchPos(canvas, e);
        if (drag) {
            const tileSizeX = canvas.width / game.array[0].length;
            const tileSizeY = canvas.height / game.array.length;

            let tileX = Math.floor(touchPos.x / tileSizeX);
            let tileY = Math.floor(touchPos.y / tileSizeY);

            game.set([[tileY, tileX]], 3)
        }
    }
}

function resize() {
    canvas.width = canvas.parentElement.offsetWidth;
    canvas.height = canvas.width*(game.array.length/game.array[0].length);
}

function drawCanvasBasedOn(arr) {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const tileSizeX = canvas.width/arr[0].length;
    const tileSizeY = canvas.height/arr.length;
    for (let r=0; r<arr.length; r++) {
        for (let c=0; c<arr[r].length; c++) {
            let fill;
            switch (arr[r][c]) {
                case 0:
                    fill = "black";
                    break;
                case 1:
                    fill = "blue";
                    break;
                case 2:
                    fill = "red";
                    break;
                case 3:
                    fill = "yellow";
                    break;
            }
            ctx.fillStyle = fill;
            ctx.beginPath();
            ctx.rect(c*tileSizeX,r*tileSizeY,tileSizeX,tileSizeY);
            ctx.fill();
        }
    }
}

let drag = false;

let cols;
let rows;
let game;

const canvas = document.getElementById("wireworld-canvas");
const ctx = canvas.getContext("2d");

let idx = 0;

function loop() {
    if (idx % 2 === 0) {
        game.step();
    } else {
        drawCanvasBasedOn(game.array);
    }
    idx++;
}

function setup() {
    addEventListener("resize", resize);

    addEventListener("mousedown", mouseDown, false);
    addEventListener("mouseup", mouseUp, false);
    addEventListener("mousemove", mouseMove, false);
    addEventListener("mouseout", mouseOut, false);

    addEventListener("touchstart", touchStart, false);
    addEventListener("touchend", touchEnd, false);
    addEventListener("touchmove", touchMove, false);
}

function init(preset=null) {
    if (preset !== null) {
        preset = preset.split("\n").map((l) => [...l].map((ch) => parseInt(ch) || 0));

        // get width and height of preset, add padding
        let minHeight = preset.length;
        let minWidth = Math.max(...preset.map((r) => r.length));

        // calculate rows and columns so that the preset fits inside the grid
        cols = (minWidth < 25) ? 25 : minWidth;
        rows = Math.ceil(cols * window.innerHeight / window.innerWidth);
        rows = (rows < minHeight) ? minHeight : rows;
        cols += 4;
        rows += 4;

        // make the preset centered in the grid
        game = new WireWorld(rows, cols);
        game.preset(preset,
            [Math.floor(rows/2 - minHeight/2), Math.floor(cols/2 - minWidth/2)]);
    } else {
        cols = Math.floor(window.innerWidth/20);
        cols = (cols < 25) ? 25 : cols;
        rows = Math.ceil(cols * window.innerHeight / window.innerWidth);

        game = new WireWorld(rows, cols);
    }
    resize();
}

export {setup, loop, init}
