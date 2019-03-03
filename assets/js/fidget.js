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
    if (this.accel < 0) {
        this.accel = 0;
    }
};

const fidget = new Fidget(canvas.width/2,canvas.height/2,canvas.width/2-50);

function loop() {
    // ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = "white";
    ctx.beginPath();
    ctx.rect(0,0,canvas.width,canvas.height);
    ctx.fill();

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
        if ((fidget.x-fidget.size < mouseX < fidget.x+fidget.size && fidget.y-fidget.size < mouseY < fidget.y+fidget.size)) {
            let x;
            let y;
            if (mouseY > fidget.y) {
                x = e.movementX;
            } else {
                x = -e.movementX;
            }
            if (mouseX > fidget.x) {
                y = -e.movementY;
            } else {
                y = e.movementY;
            }
            fidget.accel = (x+y)/100;
        }
    }
}

function touchStart(e) {
    drag = true;
}
function touchEnd(e) {
    drag = false;
}
function touchMove(e) {
    if (e.touches) {
        if (e.touches.length === 1) {
            const touch = e.touches[0];
            let touchX = touch.offsetX;
            let touchY = touch.offsetY;
            if (drag) {
                if ((fidget.x-fidget.size < touchX < fidget.x+fidget.size && fidget.y-fidget.size < touchY < fidget.y+fidget.size)) {
                    let x;
                    let y;
                    if (touchY > fidget.y) {
                        x = e.movementX;
                    } else {
                        x = -e.movementX;
                    }
                    if (touchX > fidget.x) {
                        y = -e.movementY;
                    } else {
                        y = e.movementY;
                    }
                    fidget.accel = (x+y)/100;
                }
            }
        }
    }
}

function resize() {
    canvas.width = 120+window.innerWidth/5;
	canvas.height = 120+window.innerWidth/5;
	fidget.x = canvas.width/2;
	fidget.y = canvas.height/2;
	fidget.size = canvas.width/2-50;
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
