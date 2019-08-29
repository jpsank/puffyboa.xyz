
class Line {
  constructor(x, y, a) {
    this.x = x;
    this.y = y;
    this.a = a;
  }

  forward(m) {
    this.x += Math.cos(this.a)*m;
    this.y += Math.sin(this.a)*m;
  }

  update() {
    if (this.x > width) {
      this.a = -this.a+Math.PI;
      this.x = 2*width-this.x;
    } else if (this.x < 0) {
      this.a = -this.a+Math.PI;
      this.x = -this.x;
    }
    if (this.y > height) {
      this.a *= -1;
      this.y = 2*height-this.y;
    } else if (this.y < 0) {
      this.a *= -1;
      this.y = -this.y;
    }
  }

}

const NUM_LINES = 18;

let song;
let fft;
let lines;

let lastSum;
let sumSum = 0;


function preload() {

  let fileInput = document.querySelector("#file");
  fileInput.addEventListener("change",function(){
    if (song) {
      song.stop();
    }
    let file = fileInput.files[0];
    const objectURL = window.URL.createObjectURL(file);
    song = loadSound(objectURL, init)
  });

}

function openFullscreen(elem) {
  if (elem.requestFullscreen) {
    elem.requestFullscreen();
  } else if (elem.mozRequestFullScreen) { /* Firefox */
    elem.mozRequestFullScreen();
  } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
    elem.webkitRequestFullscreen();
  } else if (elem.msRequestFullscreen) { /* IE/Edge */
    elem.msRequestFullscreen();
  }
}

function init() {

  lines = [];
  for (let i=0; i<NUM_LINES; i++) {
    lines.push(new Line(width / 2, height / 2, 2*Math.PI * i/NUM_LINES));
  }

  song.play();
  fft = new p5.FFT();
  fft.setInput(song);
}

function windowResized() {
  resizeCanvas(windowWidth, windowHeight);
  lines = [];
  for (let i=0; i<NUM_LINES; i++) {
    lines.push(new Line(width / 2, height / 2, 2*Math.PI * i/NUM_LINES));
  }
}

function setup() {
  createCanvas(windowWidth,windowHeight);
  background(0);
}

function draw() {
  if (song && song.isPlaying()) {

    const spectrum = fft.analyze();

    let fftSum = 0;
    for (let j = 0; j < spectrum.length; j++) {
      fftSum += spectrum[j];
    }

    const bass = fft.getEnergy("bass");
    const lowMid = fft.getEnergy("lowMid");
    const mid = fft.getEnergy("mid");
    const highMid = fft.getEnergy("highMid");
    const treble = fft.getEnergy("treble");
    const max = Math.max(bass, lowMid, mid, highMid, treble);

    const red = 255 * (bass + lowMid + mid * 3 / 4) / (1 + max);
    const green = 255 * (mid / 4 + highMid) / (1 + max);
    const blue = 255 * (treble) / (1 + max);

    if (lastSum) {
      let diff = (fftSum - lastSum) / spectrum.length;
      if (diff > 2) {
        background(red / 25 * diff, green / 25 * diff, blue / 25 * diff);
      } else if (diff > 1) {
        background(0);
      }
    }

    let stack = 0;
    for (let i = 0; i < 5; i++) {
      let energy = [bass, lowMid, mid, highMid, treble][i];
      let size = energy / 10;
      let weight = energy / 20;

      for (let j = 0; j < lines.length; j++) {
        let l = lines[j];
        l.forward(Math.pow(fftSum / spectrum.length / 50, 2));
        l.update();

        let a = l.a;
        a += i / 5 * sumSum / 250 / spectrum.length;

        let x1 = l.x + Math.cos(a) * stack;
        let y1 = l.y + Math.sin(a) * stack;
        // let x2 = x1 + Math.cos(l.a) * size;
        // let y2 = y1 + Math.sin(l.a) * size;

        strokeCap(SQUARE);
        stroke(red - treble * i, green, blue - bass * (5 - i));
        strokeWeight(weight);
        // line(x1, y1, x2,y2);
        fill(red - treble * i, green - (20 * fftSum / spectrum.length) * i, blue);
        ellipse(x1, y1, size, size);

        stroke(red, green, blue, bass);
        fill(red, green, blue, bass - treble);
        ellipse(x1, y1, size * 2 + treble - bass / 3, size * 2 + treble - bass / 3);
      }
      stack += size + (treble + highMid);
    }

    sumSum += fftSum;
    lastSum = fftSum;
  }
}

function sum(arr) {
  let sum = 0;
  arr.forEach((a) => sum += a);
  return sum;
}

function touchStarted() {
  getAudioContext().resume()
}
