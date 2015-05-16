$(function() {
	// Deck initialization
	$.deck('.slide');

    prettyPrint();
	/*
	$('#style-themes').change(function() {
		$('#style-theme-link').attr('href', $(this).val());
	});
	
	$('#transition-themes').change(function() {
		$('#transition-theme-link').attr('href', $(this).val());
	});
	*/

    $('#filelist a').click(function(){
        alert("Size: " + this.dataset.size+"\nType: " + this.dataset.type);
    });

    var object = {
        foo: 'bar',
        hello: 'world'
    };
    $('#json-string').text(JSON.stringify(object));

    buildSpinner({ x : 50, y : 50, size : 20, degrees : 30 });

    video2canvas();
});

function drawCanvas() {
    var context = document.getElementById('canvas').getContext('2d');
    var width = 130;  // Triangle Width
    var height = 100; // Triangle Height
    var padding = 5;

    // Draw a path
    context.beginPath();
    context.moveTo(padding + width/2, padding);        // Top Corner
    context.lineTo(padding + width, height + padding); // Bottom Right
    context.lineTo(padding, height + padding);         // Bottom Left
    context.closePath();

    // Fill the path
    context.fillStyle = '#0000FF';
    context.fill();
}

function buildSpinner(data) {

  var canvas = document.getElementById('canvasSpinner');

  var ctx = canvas.getContext("2d"),
    i = 0, degrees = data.degrees, loops = 0, degreesList = [];

  for (i = 0; i < degrees; i++) {
    degreesList.push(i);
  }

  // reset
  i = 0;

  // so I can kill it later
  window.canvasTimer = setInterval(draw, 1000/degrees);

  function reset() {
    ctx.clearRect(0,0,100,100); // clear canvas

    var left = degreesList.slice(0, 1);
    var right = degreesList.slice(1, degreesList.length);
    degreesList = right.concat(left);
  }

  function draw() {
    var c, s, e;

    var d = 0;

    if (i == 0) {
      reset();
    }

    ctx.save();

    d = degreesList[i];
    c = Math.floor(255/degrees*i);
    ctx.strokeStyle = 'rgb(' + c + ', ' + c + ', ' + c + ')';
    ctx.lineWidth = data.size;
    ctx.beginPath();
    s = Math.floor(360/degrees*(d));
    e = Math.floor(360/degrees*(d+1)) - 1;

    ctx.arc(data.x, data.y, data.size, (Math.PI/180)*s, (Math.PI/180)*e, false);
    ctx.stroke();

    ctx.restore();

    i++;
    if (i >= degrees) {
      i = 0;
    }
  }
}

var board = new Array();
var drawStack;
var SIZE = 60;
var CHIP = 5;
var context;
var currDir = 'X';
function maze() {
    init();
    explorePath(1,1);
    drawBoard();
}

function init() {
    var canvas = document.getElementById("canvasBoard");
    canvas.width = CHIP*(SIZE+8);
    canvas.height = CHIP*(SIZE+8);
    if (canvas.getContext) {
        context = canvas.getContext("2d");
        context.fillStyle = "rgb(150,150,150)";
        context.fillRect(CHIP*2, CHIP*2, CHIP*(SIZE+4), CHIP*(SIZE+4));
    }
    drawStack = new Array();
    for(i=0; i< SIZE; i++) {
        board[i] = new Array();
        for(j=0; j<SIZE; j++) {
            board[i][j] = 0;
            context.fillStyle = "rgb(0,0,0)";
            context.fillRect(CHIP*(4+i), CHIP*(4+j), CHIP, CHIP);
        }
    }
}

function drawWhite(i, j) {
    context.fillStyle = "rgb(255,255,255)";
    context.fillRect(CHIP*(4+i), CHIP*(4+j), CHIP, CHIP);
}

function drawBoard() {
    i = drawStack.shift();
    j = drawStack.shift();
    drawWhite(i,j);
    if(drawStack.length > 0) {
        setTimeout('drawBoard()', 10);
    }
}

function explorePath(i,j) {
    board[i][j] =1;
    drawStack.push(i,j);
    var options = new Array("U", "D", "F", "B");
    while(options.length > 0) {
        var selectedOption = selectValue(options);
        if(isValidMove(i, j, selectedOption)) {
            if("U" == selectedOption)
                explorePath(i+1, j);
            if("D" == selectedOption)
                explorePath(i-1, j);
            if("F" == selectedOption)
                explorePath(i, j+1);
            if("B" == selectedOption)
                explorePath(i, j-1);
        }
    }
}

function selectValue(options) {
    var selected = Math.floor(Math.random()*options.length);
    if(Math.floor(Math.random()*8) > 0) {
        for(k=0; k< options.length; k++) {
            if(options[k] == currDir) {
                selected = k;
                break;
            }
        }
    }
    var selectedOption = options.splice(selected, 1);
    currDir = selectedOption;
    return selectedOption;
}
function isValidMove(i, j, selectedOption) {
    newI=i;
    newJ=j;
    if("U" == selectedOption) {
        newI = i+1;
    }
    if("D" == selectedOption) {
        newI = i-1;
    }
    if("F" == selectedOption) {
        newJ = j+1;
    }
    if("B" == selectedOption) {
        newJ = j-1;
    }
    if(newI == SIZE -1 || newI == 0 || newJ == SIZE-1 || newJ ==0)
        return false;
    if(board[newI][newJ]==1)
        return false;
    for(x = -1; x < 2; x++) {
        for(y = -1; y < 2; y++) {
            if(board[newI+x][newJ+y]==1) {
                if((i==newI+x &&j==newJ)||(j==newJ+y && i == newI)) {

                }
                else
                    return false;
            }

        }
    }
    return true;
}

function video2canvas() {
    var video = document.getElementById('video2canvas');
    var canvas1 = document.getElementById('canvas2videoContainer');
    var context1 = canvas1.getContext('2d');
    var canvas2 = document.getElementById('canvas2video');
    var context2 = canvas2.getContext('2d');
    var effekt = document.getElementById('videoeffekt');
    var interval;

    // Beim Video-Start die Operation starten
    video.addEventListener('play', function(){
        interval = setInterval(function(){
            // Frames kopieren...
            context1.drawImage(video, 0, 0, 320, 180);
            // ... die Canvas-Pixel auslesen...
            imgData = context1.getImageData(0, 0, 320, 180);
            // ... den Effekt anwenden...
            imgData.data = effekte[effekt.value].apply(null, [imgData.data]);
            // ... und auf die zweite Canvas schreiben
            context2.putImageData(imgData, 0, 0);
        }, 25);
    }, false);

    // Beim Video-Stop die Operation beenden
    video.addEventListener('stop', function(){
        clearInterval(interval);
    }, false);
    video.addEventListener('pause', function(){
        clearInterval(interval);
    }, false);

    var effekte = {

        // Kein Effekt
        'none': function(data){
            return data;
        },

        // Acid-Effekt
        'acid': function(data){
            var i = 0;
            var len = data.length;
            while(i < len){
                for(var j = 0; j < 4; j++){
                    data[i] = (j != 3) ? 255 - data[i] : data[i];
                    i++;
                }
            }
            return data;
        },

        // Ameisenkrieg-Effekt
        'ameisenkrieg': function(data){
            var i = 0;
            var flackerfaktor = Math.round(Math.random() * 80) + 10;
            var stoerung1 = Math.round(Math.random() * 355) * 4 * 320;
            var stoerung2 = Math.round(Math.random() * 355) * 4 * 320;
            var len = data.length;
            while(i < len){
                if(i == stoerung1 || i == stoerung2){
                    for(var j = 0; j < 320 * 4; j++){
                        data[i] = 255;
                        i++;
                    }
                }
                else{
                    for(var j = 0; j < 4; j++){
                        if(j != 3){
                            if(Math.random() > 0.5 && data[i] < 255 - flackerfaktor){
                                data[i] = data[i] + Math.round(Math.random() * flackerfaktor);
                            }
                            else if(data[i] > flackerfaktor){
                                data[i] = data[i] - Math.round(Math.random() * flackerfaktor);
                            }
                            else{
                                data[i] = data[i];
                            }
                        }
                        else{
                            data[i] = data[i];
                        }
                        i++;
                    }
                }
            }
            return data;
        },


        // Überwachungskamera-Effekt
        'schaeuble': function(data){
            var i = 0;
            var rauschen = 20;
            var farbshift = 60;
            var abdunkeln = 60;
            var len = data.length;
            while(i < len){
                for(var j = 0; j < 4; j++){
                    if(j != 3){
                        // Rauschen
                        if(Math.random() > 0.5 && data[i] < 255 - rauschen){
                            var pixelwert = data[i] + Math.round(Math.random() * rauschen);
                        }
                        else if(data[i] > rauschen){
                            var pixelwert = data[i] - Math.round(Math.random() * rauschen);
                        }
                        else{
                            var pixelwert = data[i];
                        }
                        // Farbshift
                        if(j == 1){
                            pixelwert = (pixelwert < 255 - farbshift / 2) ? pixelwert + farbshift / 2 : 255;
                        }
                        else{
                            pixelwert = (pixelwert > farbshift) ? pixelwert - farbshift : 0;
                        }
                        // Abdunkeln
                        pixelwert = (pixelwert > abdunkeln) ? pixelwert - abdunkeln : 0;
                    }
                    else{
                        var pixelwert = data[i];
                    }
                    data[i] = pixelwert;
                    i++;
                }
            }
            return data;
        }

    }
}
