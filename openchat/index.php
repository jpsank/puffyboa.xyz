<?php

include 'SimpleImage.php';

function store_uploaded_image($file, $target_file, $max_img_dimens) {

	$image = new SimpleImage();
	$image->load($file);
	if ($image->getWidth() > $max_img_dimens) {
		$image->resizeToWidth($max_img_dimens);
	}
	if ($image->getHeight() > $max_img_dimens) {
		$image->resizeToHeight($max_img_dimens);
	}
	$image->save($target_file);
	return $target_file;

}

require_once('dbconf.php');

class DBHandler {
	public $conn;
	public $data;

	function __construct($conn) {
		$this->conn = $conn;
	}

	function init() {

		// Create database if not already created

		$sql = "CREATE DATABASE IF NOT EXISTS openchat";
		$this->conn->exec($sql);

		// Create table if not already created

		$sql = "CREATE TABLE IF NOT EXISTS Messages (
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
		message VARCHAR(2000) NOT NULL,
		post_date TIMESTAMP,
		has_attachment bit NOT NULL DEFAULT (0)
		)";
		$this->conn->exec($sql);

		// Fetch messages from database

		$sql = "SELECT id, message, post_date, has_attachment FROM Messages ORDER BY post_date DESC";
		$stmt = $this->conn->prepare($sql);

		$this->data = array();
		if ($stmt->execute()) {
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$this->data[] = $row;
			}
		}
	}

	function display() {
		foreach ($this->data as $idx => $val) {
			$id = $val["id"];
			$message = htmlspecialchars($val["message"]);
			$post_date = strtotime($val["post_date"]);
			$has_attachment = $val["has_attachment"];

			$t = time()-$post_date;
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
			$html = "<div class='message'>";
			$html .= "<p class='t'>$t $u ago</p>";
			$html .= "<p class='m'>$message</p>";
			if ($has_attachment) {
				$file_path = 'uploads/' . $id;

				$finfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file_path);
				if (substr($finfo,0,4) == "text") {
					$file_text = file_get_contents($file_path);
					$html .= "<img src='$file_path' alt='$file_text'>";
				} else {
					$html .= "<img src='$file_path'>";
				}
			}
			$html .= "</div>";
			echo $html;
		}
	}

	function postMessage() {
		$text = $_POST['text'];
		$time = strftime("%Y-%m-%d %H:%M:%S",time());
		if ($_FILES["userfile"]["error"] == UPLOAD_ERR_NO_FILE) {
			$attached = 0;
		} else {
			$attached = 1;
		}

		$sql = "INSERT INTO Messages (message, post_date, has_attachment) VALUES ('$text','$time',$attached);";
		$this->conn->exec($sql);

		if ($attached) {
			if ($_FILES["userfile"]["error"] == UPLOAD_ERR_INI_SIZE) {
				$error = "Image upload error: File too large";
			} else if ($_FILES["userfile"]["error"] != 0) {
				$error = "Image upload error: Code " . $_FILES["userfile"]["error"];
			}

			$last_id = $this->conn->lastInsertId();
			$target_file = 'uploads/' . $last_id;

			if (isset($error)) {
				file_put_contents($target_file, $error);
			} else {
				$tmp_file = $_FILES["userfile"]["tmp_name"];
				store_uploaded_image($tmp_file, $target_file, 500);
			}
		}


	}

	function close() {
		$this->conn = null;
	}

}

$pdo = new PDO($driver, $user, $pass, $attr);
$handler = new DBHandler($pdo);
$handler->init();

// } catch (PDOException $e) {
// 	echo "PDOException: " . $e->getMessage();
// 	die();
// }

$charLimit = 2000;

// Did the user submit a message
if (isset($_POST['text'])) {
	if ($_POST['text'] != '') {
		if (strlen($_POST['text']) <= $charLimit) {
			$handler->postMessage();
		}
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
		<h1>OpenChat</h1>
		<p>welcome to openchat, an open ledger for posting anonymous public messages</p>
	</section>

	<section id="main">

		<form id="form" enctype="multipart/form-data" method="post">
		    <input type="text" name="text" placeholder="type something" maxlength="500">
		    <input name="userfile" accept="image/*" type="file" />
		    <input type="submit">
		</form>

		<div id="ledger">
			<?php $handler->display(); $handler->close(); ?>
		</div>

	</section>

</body>
</html>
