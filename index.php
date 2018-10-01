<?php

function readJSON($fName) {
	$fHandle = fopen($fName, 'r');
	$content = fread($fHandle, filesize($fName));
	fclose($fHandle);
	return json_decode($content, TRUE);
}
function writeJSON($fName, $json) {
	$fHandle = fopen($fName, 'w');
	fwrite($fHandle, json_encode($json));
	fclose($fHandle);
}

function displayMessages($DATA) {
	$DATA = array_reverse($DATA);
	foreach ($DATA as $key => $val) {
		$message = htmlspecialchars($val[0]);

		$t = time()-$val[1];
		$u = "seconds";
		if ($t > 60) {
			$t = round($t/60);
			$u = "minutes";

			if ($t > 60) {
				$t = round($t/60);
				$u = "hours";

				if ($t > 24) {
					$t = round($t/24);
					$u = "days";
				}
			}
		}
		echo "<div class='message'><p class='t'>$t $u ago</p><p class='m'>$message</p></div>";
	}
}

$fName = 'data.json';

// Did the user submit a message
if (isset($_POST['text'])) {
	if ($_POST['text'] != '') {
		// Post message to JSON
		$DATA = readJSON($fName);
		$DATA[] = [$_POST['text'],time()];
		writeJSON($fName, $DATA);
	}
	// Reset
	header('Location: index.php');
}

?>

<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>OpenText - puffyboa.xyz</title>
	<link rel="shortcut icon" href="img/favicon.png" />
</head>

<body>

	<section id="jumbo">
		<h1>OpenText</h1>
		<p>welcome to opentext, an open ledger for posting anonymous public messages</p>
	</section>

	<section id="main">

		<form id="form" method="post">
		    <input type="text" name="text" placeholder="type something" maxlength="500">
		    <input type="submit">
		</form>

		<div id="ledger">
			<?php displayMessages(readJSON($fName)); ?>
		</div>

	</section>

</body>
</html>
