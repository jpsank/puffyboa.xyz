<?php

function joinPaths() {
    $args = func_get_args();
    $paths = array();
    foreach ($args as $arg) {
        $paths = array_merge($paths, (array)$arg);
    }
    $paths = array_map(function($p) { return trim($p, "/"); }, $paths);
    $paths = array_filter($paths);
    return join('/', $paths);
}

$TEXT_DIR = 'texts';

$TEXTS = [];

$folders = scandir($TEXT_DIR);
foreach($folders as $folder) {
    $folderPath = joinPaths($TEXT_DIR, $folder);
    if (is_dir($folderPath) && substr($folder, -1) != ".") {
        $files = scandir($folderPath);
        foreach($files as $file) {
            if (substr($file, -4) === ".txt") {
                if (!key_exists($folder,$TEXTS)) {
                    $TEXTS[$folder] = [];
                }
                array_push($TEXTS[$folder], $file);
            }
        }
    }
}

?>

<html lang="en">

<head>
    <link rel="stylesheet" type="text/css" href="../assets/css/shared.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Speedreed - puffyboa.xyz</title>
</head>

<body>

    <div class="back-to-home">
        <a href="../index.html">puffyboa.xyz</a>
        <a href="index.php">Speedreed</a>
    </div>

    <div class="jumbo">
        <h1>Speedreed</h1>
    </div>

    <div id="main">
    <?php

    if (!isset($_GET["file"]) || $_GET["file"] == "") {

        echo "<div id='library'>";
        foreach($TEXTS as $folder=>$files) {
            $author = str_replace("_"," ",$folder);
            echo "<div class='author'><h3>$author</h3>";

            $i = 0;
            foreach($files as $file) {
                $title = ucwords(str_replace("_"," ",substr($file,0,-4)));
                echo "<p><a href='?file=$folder:$file'>$title</a></p>";
                $i++;
            }

            echo "</div>";
        }
        echo "</div>";

    } else {

        echo "<div id='text'>";

        $splitPath = explode(":", $_GET["file"]);
        $folder = $splitPath[0];
        $file = $splitPath[1];
        if ($TEXTS[$folder] && in_array($file, $TEXTS[$folder])) {
            $fp = joinPaths($TEXT_DIR, $folder, $file);
            $fh = fopen($fp, 'r');
            $pageText = htmlspecialchars(fread($fh, 1000000));

            $lines = explode("\n", $pageText);
            $lines = array_map(function ($l) { return preg_split("/[\s]+/", $l); }, $lines);
//            if (sizeof($lines) > 5000) {
//                $lines = array_slice($lines,0,5000);
//            }
//            if (isset($_GET["loc"])) {
//                $loc = (int)$_GET["loc"];
//                $lines = array_slice($lines, $loc);
//            }
            foreach ($lines as $l=>$line) {
                echo "<div class='line' id='$l'>";
                foreach ($line as $word) {
                    echo "<p class='word'>" . $word . "</p>";
                }
                echo "</div>";
            }

        } else {
            echo "File not found.";
        }
        echo "</div>";

    }
    ?>
    </div>
    <script src="script.js"></script>
</body>
</html>