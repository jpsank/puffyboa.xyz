<?php

include 'SimpleImage.php';
include 'DBHandler.php';

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
        <a href="../index.html">puffyboa.xyz</a>
        <a href="index.php">OpenChat</a>
    </div>

	<section id="jumbo">
		<h1>OpenChat</h1>
		<p>welcome to openchat, an open ledger for posting anonymous public messages</p>
	</section>

	<section id="main">

		<form id="form" enctype="multipart/form-data" action="?page=1" method="post">
		    <input type="text" name="text" placeholder="type something" maxlength="1000" required="required">
		    <input name="userfile" accept="image/*" type="file" />
		    <input type="submit">
		</form>

		<div id="ledger">
            <?php
            if (isset($_GET["page"]) && $_GET["page"] != "") {
                $page = (int)$_GET["page"]-1;
                $handler->display($page);
            } else {
                $handler->display();
            }
            $handler->close();
            ?>
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

	function fetchUpdates() {
	    const id = ledger.children[0].id;
        const xhr = new XMLHttpRequest();
	    xhr.open('GET', `fetch.php?last_id=${id}`);
	    xhr.onreadystatechange = () => {if (xhr.readyState === 4 && xhr.status === 200) {
	        addMessages(xhr.response);
        }};
	    xhr.send();

        // loops update fetcher every 5 seconds
	    setTimeout(fetchUpdates, 5000);
    }

    function addMessages(messages) {
	    messages = JSON.parse(messages);
        for (const msg of messages) {
            let time_passed = (Date.now() - Date.parse(msg["post_date"] + " UTC"))/1000;
            let units = 'seconds';
            if (time_passed > 60) {
                time_passed /= 60;
                units = 'minutes';

                if (time_passed > 60) {
                    time_passed /= 60;
                    units = 'hours';

                    if (time_passed > 24) {
                        time_passed /= 24;
                        units = 'days';
                    }
                }
            }
            time_passed = Math.round(time_passed);

            const div = document.createElement('div');
            div.id = msg.id;
            div.classList.add('message');

            const time = document.createElement('p');
            time.classList.add('t');
            time.textContent = `${time_passed} ${units} ago`;
            div.appendChild(time);

            const message = document.createElement('p');
            message.classList.add('m');
            message.textContent = msg.message;
            div.appendChild(message);

            if (msg["has_attachment"]) {
                const img = document.createElement('img');
                img.src = `uploads/${msg.id}`;
                div.appendChild(img);
            }

            // adds the new div to the top of the ledger (hopefully)
            ledger.insertBefore(div, ledger.children[0]);
        }
    }

    const ledger = document.getElementById('ledger');
    setTimeout(fetchUpdates, 5000);

</script>
