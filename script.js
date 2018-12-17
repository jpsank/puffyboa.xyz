
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
		//console.log(this.array);
	}
}

function createMatrix(rows,cols) {
	const matrix = [];
	for (let i=0; i<rows; i++) {
		matrix[i] = new Array(cols).fill(0);
	}
	return matrix;
}

var cols = Math.round(screen.width/24); // default 60
if (cols > 60) {cols = 60;} else if (cols < 20) {cols = 20;}
const rows = Math.round(cols/2);
//console.log(cols,rows);
const game = new GameOfLife(createMatrix(rows,cols));

function updateTableBasedOn(arr) {
	const tbody = document.querySelector('#table-of-life tbody');
	tbody.innerHTML = '';
	for (let r=0; r<arr.length; r++) {
		let str = '<tr>';
		for (let c=0; c<arr[r].length; c++) {
			if (arr[r][c] === 0) {
				str += `<td></td>`
			} else if (arr[r][c] === 1){
				str += '<td class="on"></td>'
			}
		}
		str += '</tr>\n';
		tbody.innerHTML += str;
	}
}
function draw() {
	game.step();
	updateTableBasedOn(game.array);
	window.setTimeout(draw,100);
}

function setup() {
	var o = Math.round(rows/2);
	game.toggle([[o,o],[o+1,o],[o-1,o],[o+1,o+1],[o,o-1]])
	draw();
}
window.onload = setup;
