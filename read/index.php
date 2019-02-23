<html>
<head>
	<link rel="stylesheet" type="text/css" href="style.css">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<title>Read</title>
</head>

<body>

	<div id="main">
			<?php 
			if ($_GET["file"] == "") {

				echo "<div id='library'>";
				$folders = scandir('texts/');
				foreach($folders as $folder) {
					if (is_dir('texts/' . $folder) && substr($folder, -1) != ".") {
						$files = scandir('texts/' . $folder);
						$author = str_replace("_"," ",$folder);
						echo sprintf("<div class='author'><h3>%s</h3>", $author);
						foreach($files as $file) {
							if (substr($file, -4) === ".txt") {
								$title = ucwords(str_replace("_"," ",substr($file,0,-4)));
								echo sprintf("<p><a href='?file=%s/%s'>%s</a></p>", $folder, $file, $title);
							}
						}
						echo "</div>";
					}
				}
				echo "</div>";

			} else {

				echo "<div id='text'>";
				$file = sprintf("texts/%s", $_GET["file"]);
				if (!file_exists($file)) {
					echo "File not found.";
				} else {
					$fh = fopen($file, 'r');
					$pageText = fread($fh, 1000000);
					echo nl2br(htmlspecialchars($pageText));
				}
				echo "</div>";

			}
			?>
	</div>

	<script src="conway.js"></script>

</body>
</html>
