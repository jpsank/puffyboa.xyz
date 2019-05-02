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
	$image->save($target_file,$image->image_type);
	return $target_file;

}

class DBHandler {
	public $conn;
	public $data;

	function __construct($conn) {
		$this->conn = $conn;
	}

	function init() {

        if (!file_exists("uploads")) {
            $oldmask = umask(0);
            mkdir("uploads", 0777);
            umask($oldmask);
        }

		// Create table if not already created

		$sql = "CREATE TABLE IF NOT EXISTS Messages (
		id INTEGER PRIMARY KEY, 
		message VARCHAR(2000) NOT NULL,
		post_date TIMESTAMP,
		has_attachment bit NOT NULL DEFAULT (0)
		)";
		$this->conn->exec($sql);

		// Fetch messages from database

		$sql = "SELECT * FROM Messages ORDER BY post_date DESC";
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
			$html = "<div id='$id' class='message'>";
			$html .= "<p class='t'>$t $u ago</p>";
			$html .= "<p class='m'>$message</p>";
			if ($has_attachment) {
				$file_path = 'uploads/' . $id;

				$finfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file_path);
				$file_text = "";
				if (substr($finfo,0,4) == "text") {
					$file_text = file_get_contents($file_path);
				}
				$html .= "<img src='$file_path' alt='$file_text'>";
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

		$sql = "INSERT INTO Messages (message, post_date, has_attachment) VALUES (:message,:post_date,:attached);";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':message', $text);
        $stmt->bindParam(':post_date', $time);
        $stmt->bindParam(':attached', $attached);
        $stmt->execute();

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

$db_folder = "../db";

if (!file_exists($db_folder)) {
    $oldmask = umask(0);
    mkdir($db_folder, 0777);
    umask($oldmask);
}

$pdo = new PDO("sqlite:$db_folder/openchat.db");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
		} else {
			echo "Message exceeds 2000 character limit.";
			die();
		}
	}
	// Reset
	header('Location: index.php');
}

?>

<html lang="en">

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="../assets/css/shared.css">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>OpenChat - puffyboa.xyz</title>
	<link rel="shortcut icon" href="../assets/img/favicon.png" />
</head>

<body>

    <div class="back-to-home">
        <a href="../index.html">Home</a>
    </div>

	<section id="jumbo">
		<h1>OpenChat</h1>
		<p>welcome to openchat, an open ledger for posting anonymous public messages</p>
	</section>

	<section id="main">

		<form id="form" enctype="multipart/form-data" method="post">
		    <input type="text" name="text" placeholder="type something" maxlength="1000" required="required">
		    <input name="userfile" accept="image/*" type="file" />
		    <input type="submit">
		</form>

		<div id="ledger">
			<?php $handler->display(); $handler->close(); ?>
		</div>

	</section>

</body>
</html>

<script>
	
	function setReply(id) {
		let input = document.querySelector('input[name="text"]');
		input.value = "@" + id;
	}

	function toggleDisplay(id) {
		let img = document.querySelector(`div.message[id="${id}"] img`);
		if (img.style.display === "block") {
			img.style.display = "none";
		} else {
			img.style.display = "block";
		}
	}

</script>
