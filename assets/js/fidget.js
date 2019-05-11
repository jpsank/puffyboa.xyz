const canvas = document.getElementById("fidget");
const ctx = canvas.getContext("2d");
let drag = false;

Math.radians = function(degrees) {
    return degrees * Math.PI / 180;
};

// make Fidget class

const Fidget = function(x,y,size){
    this.x = x;
    this.y = y;
    this.size = size;
    this.rot = 0;
    this.accel = 0;
    this.stroke = "black";
};
Fidget.prototype.draw = function() {
    ctx.strokeStyle = this.stroke;
    for (let i=0;i<3;i++) {
        let angle = i * 2*Math.PI/3;
        let x = this.x-Math.cos(this.rot+angle)*this.size;
        let y = this.y+Math.sin(this.rot+angle)*this.size;
        ctx.lineWidth = this.size/2.5;
        ctx.lineCap = "round";
        ctx.beginPath();
        ctx.moveTo(this.x, this.y);
        ctx.lineTo(x, y);
        ctx.stroke();

        ctx.lineWidth = 1;
        ctx.fillStyle = ["cyan", "lime", "yellow"][i];
        ctx.beginPath();
        ctx.ellipse(x,y,this.size/6,this.size/6,0,0,2 * Math.PI);
        ctx.fill();
    }
    ctx.fillStyle = "red";
    ctx.beginPath();
    ctx.ellipse(this.x,this.y,this.size/5,this.size/5,0,0,2 * Math.PI);
    ctx.fill();
};
Fidget.prototype.spin = function () {
    this.rot += this.accel;
    this.accel *= .99;
};

const fidget = new Fidget(canvas.width/2,canvas.height/2,canvas.width/2-50);

function loop() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    fidget.draw();
    fidget.spin();
}

// Set up mouse controls

function mouseDown(e) {
    drag = true;
}
function mouseUp(e) {
    drag = false;
}
function mouseOut(e) {
    drag = false;
}
function mouseMove(e) {
    let mouseX = e.offsetX;
    let mouseY = e.offsetY;
    if (drag) {
        dragSpin(mouseX-e.movementX, mouseY-e.movementY, mouseX,mouseY);
    }
}

function dragSpin(startX, startY, endX, endY) {
    if ((fidget.x-fidget.size < endX < fidget.x+fidget.size && fidget.y-fidget.size < endY < fidget.y+fidget.size)) {
        let x1 = startX-fidget.x;
        let y1 = -(startY-fidget.y);
        let x2 = endX-fidget.x;
        let y2 = -(endY-fidget.y);

        let a1 = Math.atan2(y1,x1);  // angle from fidget origin to starting point
        let a2 = Math.atan2(y2,x2);  // angle from fidget origin to ending point

        fidget.accel = a2-a1;
    }
}

let touchPos = null;

function touchStart(e) {
    if (e.target === canvas) {
        e.preventDefault();
    }
    touchPos = getTouchPos(canvas, e);
    drag = true;
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
    }
    const newPos = getTouchPos(canvas, e);
    if (drag) {
        dragSpin(touchPos.x, touchPos.y, newPos.x, newPos.y);
    }
    touchPos = newPos;
}
function getTouchPos(canvasDom, touchEvent) {
    const rect = canvasDom.getBoundingClientRect();
    return {
        x: touchEvent.touches[0].clientX - rect.left,
        y: touchEvent.touches[0].clientY - rect.top
    };
}

function resize() {
    // let size = 200;
    // size = size < 100 ? 100 : size;
    // canvas.width = size;
    // canvas.height = size;
	// fidget.x = canvas.width/2;
	// fidget.y = canvas.height/2;
	// fidget.size = canvas.width/2-canvas.width/10;
}

function setup() {
    canvas.addEventListener('mousedown', mouseDown, false);
    canvas.addEventListener('mouseup', mouseUp, false);
    canvas.addEventListener ("mouseout", mouseOut, false);
    canvas.addEventListener('mousemove', mouseMove, false);

    canvas.addEventListener('touchstart', touchStart, false);
    canvas.addEventListener('touchend', touchEnd, false);
    canvas.addEventListener('touchmove', touchMove, false);
    resize();
}

export {setup, loop, resize};
