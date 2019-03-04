
class GameOfLife {
	constructor(array) {
		this.array = array;
	}
	getNeighbors(r, c) {
		const offsets = [[1,1],[1,0],[1,-1],[0,1],[0,-1],[-1,1],[-1,0],[-1,-1]];
		const neighs = [];
		for (const o of offsets) {
			let r2 = (r+o[0]) % this.array.length;
			if (r2 < 0) {r2 += this.array.length;}
			let c2 = (c+o[1]) % this.array[r2].length;
			if (c2 < 0) {c2 += this.array[r2].length;}
			if (this.array[r2][c2] === 1) {
				neighs.push([r2,c2]);
			}
		}
		return neighs;
	}
	getTmpArray() {
		let newArray = [];
		for (var i = 0; i < this.array.length; i++) {
    		newArray[i] = this.array[i].slice();
		}
		return newArray;
	}
	step() {
		const tmpArray = this.getTmpArray();
		for (let r=0; r < this.array.length; r++) {
			for (let c=0; c < this.array[r].length; c++) {
				const neighs = this.getNeighbors(r,c);
				if (this.array[r][c] === 1) {
					if (neighs.length > 3 || neighs.length < 2) {
						tmpArray[r][c] = 0;
					}
				} else if (this.array[r][c] === 0) {
					if (neighs.length === 3) {
						tmpArray[r][c] = 1;
					}
				}
			}
		}
		this.array = tmpArray;
	}
	toggle(points) {
		for (const p of points) {
			//console.log(p);
			if (this.array[p[0]][p[1]] === 0) {
				this.array[p[0]][p[1]] = 1;
			} else {
				this.array[p[0]][p[1]] = 0;
			}
		}
	}
}

function createMatrix(rows,cols) {
	const matrix = [];
	for (let i=0; i<rows; i++) {
		matrix[i] = new Array(cols).fill(0);
	}
	return matrix;
}

let cols = Math.round(screen.width/24); // default 60
cols = cols > 80 ? 80 : cols < 30 ? 30 : cols;
let rows = Math.round(screen.height/30);
rows = rows > cols ? cols : rows;

const game = new GameOfLife(createMatrix(rows,cols));

// function updateTableBasedOn(arr) {
// 	const tbody = document.querySelector('#table-of-life tbody');
// 	tbody.innerHTML = '';
// 	for (let r=0; r<arr.length; r++) {
// 		let str = '<tr>';
// 		for (let c=0; c<arr[r].length; c++) {
// 			if (arr[r][c] === 0) {
// 				str += `<td></td>`
// 			} else if (arr[r][c] === 1){
// 				str += '<td class="on"></td>'
// 			}
// 		}
// 		str += '</tr>\n';
// 		tbody.innerHTML += str;
// 	}
// }


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
			if (arr[r][c] === 0) {
				ctx.strokeStyle = "lightgrey";
				ctx.beginPath();
				ctx.rect(c*tileSizeX,r*tileSizeY,tileSizeX,tileSizeY);
				ctx.stroke();
			} else {
				ctx.fillStyle = "black";
				ctx.beginPath();
				ctx.rect(c*tileSizeX,r*tileSizeY,tileSizeX,tileSizeY);
				ctx.fill();
			}
		}
	}
}

const canvas = document.getElementById("canvas-of-life");
resize();
const ctx = canvas.getContext("2d");

function loop() {
	game.step();
	drawCanvasBasedOn(game.array);
}

function setup() {
	// set up initial pattern
	const o = Math.round(rows/2);
	game.toggle([[o,o],[o+1,o],[o-1,o],[o+1,o+1],[o,o-1]]);
}

export {setup, loop, resize};
