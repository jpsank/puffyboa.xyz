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

if(isset($_FILES["file"])) {

	$tmp_file = $_FILES["file"]["tmp_name"];
	$imageFileType = strtolower(pathinfo($tmp_file,PATHINFO_EXTENSION));
	
	$target_file = "uploads/img";
	$index = 0;
	while (file_exists($target_file . $index)) {
		$index++;
	}
	if ($index < 100) {  // TEMPORARY FILE LIMIT (just in case)
		$target_file = $target_file . $index;


		if ($tmp_file == '') {
			$error = "Error uploading file";
		}
		if ($_FILES["file"]["error"] == UPLOAD_ERR_INI_SIZE) {
			$error = "File too large";
		}

		if (!isset($error)) {
			store_uploaded_image($tmp_file, $target_file, 500);
		}
	}
}
?>


<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>OpenSnap - puffyboa.xyz</title>
	<link rel="shortcut icon" href="img/favicon.png" />
	<link href="https://fonts.googleapis.com/css?family=Raleway|Source Sans Pro" rel="stylesheet">
</head>

<body>

	<section id="jumbo">
		<h1>OpenSnap</h1>
		<p>public picture board</p>
	</section>

	<!-- <form class="upload-form" action="upload.php" method="post" enctype="multipart/form-data">
		<p>Select image to upload</p>
		<input type="file" name="fileToUpload" id="fileToUpload" accept="image/*">
		<input type="submit" value="Upload Image" name="submit">
	</form> -->

	<p class="error"><?php echo $error; ?></p>
	<div id="drop-area">
		<!-- <form class="upload-form" action="upload.php" method="post" enctype="multipart/form-data"> -->
		<form class="upload-form">
			<p>Upload image</p>
			<input type="file" id="fileElem" accept="image/*" onchange="handleFiles(this.files)">
			<label class="button" for="fileElem">Select a file</label>
		</form>
	</div>

	<section id="images">
		<?php

		$target_dir = 'uploads/';
		$files = array_reverse(scandir($target_dir));
		foreach ($files as $i => $f) {
			$image_info = getimagesize($target_dir . $f);
			if ($image_info) {
				echo "<div class='img'><img src='$target_dir$f'></div>";
			}
		}

		?>
	</section>

	<script>
		let dropArea = document.getElementById('drop-area');

		;['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
			dropArea.addEventListener(eventName, preventDefaults, false);
		})
		function preventDefaults (e) {
			e.preventDefault();
			e.stopPropagation();
		}

		;['dragenter', 'dragover'].forEach(eventName => {
			dropArea.addEventListener(eventName, highlight, false);
		})
		;['dragleave', 'drop'].forEach(eventName => {
			dropArea.addEventListener(eventName, unhighlight, false);
		})
		function highlight(e) {
			dropArea.classList.add('highlight');
		}
		function unhighlight(e) {
			dropArea.classList.remove('highlight');
		}

		dropArea.addEventListener('drop', handleDrop, false);

		function handleDrop(e) {
			let dt = e.dataTransfer;
			let files = dt.files;
			handleFiles(files);
		}

		function handleFiles(files) {
			([...files]).forEach(uploadFile);
			location.reload();
		}

		function uploadFile(file) {
			let url = 'index.php';
			let formData = new FormData();

			formData.append('file', file);

			fetch(url, {
				method: 'POST',
				body: formData
			});
		}

	</script>

</body>
</html>

