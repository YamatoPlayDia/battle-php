<?php
    $json = file_get_contents('show-data.json');
    $rhythm_sets = json_decode($json, true);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap.min.css">
    <link rel="stylesheet" href="dynamic.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&display=swap" rel="stylesheet">
    <title>Rhythm Visualizer</title>
    <style>
        .grid {
            display: grid;
            grid-template-columns: repeat(16, 1fr);
        }
        .box {
            height: 20px;
            border: none;
            border-radius: 5px;
        }
        .blue { background-color: blue; }
        .red { background-color: red; }
        .orange { background-color: orange; }
        .green { background-color: green; }
        .grey { background-color: grey; }
        .phrase { transform: scale(0.7, 1); color: #FFF; font-weight: bold; }

        @keyframes bounce {
            0% { transform: translateY(0); opacity: 0.8;}
            50% { transform: translateY(-10px); opacity: 1;}
            100% { transform: translateY(0); opacity: 0.5; }
        }
        .bounce {
            animation: bounce 0.25s linear;
            font-size: 2rem;
            opacity: 0.7;
        }
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Noto Sans JP', sans-serif;
        }
        .container-fluid {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: -100;
        }
        .photo {
            height: 100%;
            width: 100%;
            background-size: cover;
            background-position: center;
        }
        .halftone {
            filter: contrast(3);
        }
        .halftone::after {
            content: '';
            display: block;
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            background-image: radial-gradient(#000 10%, transparent 90%);
            background-color: #fff;
            background-position: 0 0;
            background-size: 8px 8px;
            mix-blend-mode: screen;
        }
        .main-content {
            position: absolute;
            top: 12px;
            width: 100vw;
            background-color: rgba(0,0,0,0.8);
        }
    </style>
</head>
<body>
    <div class="container-fluid halftone p-0">
        <img src="bg.png" class="photo">
    </div>


    <div class="mt-5 p-5 main-content">
        <button id="start" class="btn btn-primary">Start</button>
        <button id="stop" class="btn btn-danger" disabled>Stop</button>
        <h1 id="countDown" class="text-white m-3"></h1>
        <div id="visualization" class="mt-4"></div>
    </div>


    <!-- jQuery and Bootstrap Bundle (includes Popper) -->
    <script src="jquery-2.1.3.min.js"></script>
    <script src="bootstrap.min.js"></script>
    <script>
    $(document).ready(function(){
        var rhythm_sets = <?php echo json_encode($rhythm_sets); ?>;
        var setIndex = 0;
        var countDown = 3;
        var countdownInterval;
        var rhythmInterval;
        var isPlaying = false;
        var soundDelay = -10; // The delay for the sound to play in milliseconds
        var colorChangeStartDelay = 190; // The delay for the color change to start in milliseconds

        // Web Audio API context
        var context = new (window.AudioContext || window.webkitAudioContext)();

        // Load sound sources
        var hihatBuffer, bassdrumBuffer;
        loadSound('Rock-Kit-HiHat-Tip-1.wav', function(buffer){
            hihatBuffer = buffer;
        });
        loadSound('Rock-Kit-Kick-ff-2.wav', function(buffer){
            bassdrumBuffer = buffer;
        });

        // Sound loading function
        function loadSound(url, callback) {
            var request = new XMLHttpRequest();
            request.open('GET', url, true);
            request.responseType = 'arraybuffer';

            request.onload = function() {
                context.decodeAudioData(request.response, function(buffer) {
                    callback(buffer);
                });
            }
            request.send();
        }

        function visualizeAndAnimate(setIndex) {
            var rhythm_array = rhythm_sets[setIndex].rhythm_array;
            var phrase_parts = rhythm_sets[setIndex].phrase_parts;
            var phrase = rhythm_sets[setIndex].phrase;
            var colors = ["grey", "red", "orange", "green", "blue"];
            var visualization = $('#visualization');
            var countdown = $('#countDown');
            visualization.empty();
            countdown.empty();
            var row = $('<div class="grid"></div>');
            var phrase_row = $('<div class="grid"></div>');
            var index = 0;
            for(var i = 0; i < rhythm_array.length; i++){
                var box = $('<div class="box"></div>');
                box.addClass(colors[rhythm_array[i]]);
                box.css('grid-column', `span ${rhythm_array[i]}`);
                box.attr('data-order', i.toString());
                row.append(box);
                var phrase_box = $('<div class="phrase_box"></div>');
                phrase_box.css('grid-column', `span ${Math.max(1, rhythm_array[i])}`);
                phrase_box.attr('data-order', i.toString());
                if(rhythm_array[i] > 0){
                    var label = $('<div class="phrase"></div>').text(phrase_parts[i]);
                    phrase_box.append(label);
                    index += rhythm_array[i];
                    i += rhythm_array[i] - 1; // skip the following rhythm_array[i] - 1 elements
                }
                phrase_row.append(phrase_box);
            }
            countdown.text(phrase);
            visualization.append(row);
            visualization.append(phrase_row);
            animateBoxes();
        }

        function nextSet() {
            visualizeAndAnimate(setIndex);
            playDrums(setIndex);
            setIndex = (setIndex + 1) % rhythm_sets.length;
        }

        function playDrums(setIndex) {
            // Play hihat for every beat
            for(var i = 0; i < rhythm_sets[setIndex].rhythm_array.length; i++){
                playSound(hihatBuffer, i / rhythm_sets[setIndex].rhythm_array.length * 2000 + soundDelay, null, 0.3); // Half volume for hihat
            }

            // Play bassdrum according to the rhythm_array
            for(var i = 0; i < rhythm_sets[setIndex].rhythm_array.length; i++){
                if(rhythm_sets[setIndex].rhythm_array[i] > 0){
                    playSound(bassdrumBuffer, i / rhythm_sets[setIndex].rhythm_array.length * 2000 + soundDelay, rhythm_sets[setIndex].rhythm_array[i], 1.5); // 1.5 times the volume for bassdrum
                    i += rhythm_sets[setIndex].rhythm_array[i] - 1; // skip the following rhythm_array[i] - 1 elements
                }
            }
        }

        function playSound(buffer, time, durationFactor, volume) {
            var source = context.createBufferSource();
            var gainNode = context.createGain();

            source.buffer = buffer;
            if(durationFactor){
                source.playbackRate.value = 1 / durationFactor;
            }

            gainNode.gain.value = volume;

            source.connect(gainNode);
            gainNode.connect(context.destination);

            source.start(context.currentTime + time / 1000); // Convert time to seconds
        }

        function animateBoxes() {
            var timePerFrame = 2000 / rhythm_sets[0].rhythm_array.length;
            $('.box, .phrase_box').each(function(i){
                var $this = $(this);
                var orderValue = parseInt($this.data('order'));
                setTimeout(function() {
                    $this.addClass('bounce');
                }, timePerFrame * (orderValue + 1));
                setTimeout(function() {
                    if (isPlaying) {
                        $this.fadeOut();
                    }
                }, 2000);  // Start fading out after 2 seconds
            });
            if (isPlaying) {
                rhythmInterval = setTimeout(nextSet, 2000);
            }
        }
        var colorChangeInterval; // Global variable to hold the color change interval
        var currentColor = 0; // Variable to hold the current color value

        // Function to increment color value and apply to the halftone element
        function changeColor() {
            currentColor = (currentColor + 1) % 1666;
            $(".halftone").attr("data-color", currentColor);
        }

        $("#start").click(function(){
            isPlaying = true;
            $("#start").prop("disabled", true);
            $("#stop").prop("disabled", false);
            countDown = 3;
            $("#countDown").text(countDown);
            // Create new context
            context = new (window.AudioContext || window.webkitAudioContext)();

            // Load sound sources
            loadSound('Rock-Kit-HiHat-Tip-1.wav', function(buffer){
                hihatBuffer = buffer;
            });
            loadSound('Rock-Kit-Kick-ff-2.wav', function(buffer){
                bassdrumBuffer = buffer;
            });
            countdownInterval = setInterval(function() {
                if (countDown == 0) {
                    clearInterval(countdownInterval);
                    $("#countDown").empty();  // Clear the countdown
                    nextSet();
                    // Start changing color
                    setTimeout(function() {
                        colorChangeInterval = setInterval(changeColor, 1000/10); // 1/16 seconds
                    }, colorChangeStartDelay); //
                } else {
                    $('#countDown').text(countDown);
                    countDown--;
                }
            }, 1000);
        });

        $("#stop").click(function(){
            isPlaying = false;
            $("#start").prop("disabled", false);
            $("#stop").prop("disabled", true);
            clearInterval(countdownInterval);
            clearTimeout(rhythmInterval);
            clearInterval(colorChangeInterval); // Clear color change interval
            $('.box, .phrase_box').stop().removeAttr('style');
            setIndex = 0;
            context.suspend(); // Stop all audio
            currentColor = 0; // Reset color value
            $(".halftone").attr("data-color", currentColor); // Reset color of halftone element
        });
    });
    </script>
</body>
</html>