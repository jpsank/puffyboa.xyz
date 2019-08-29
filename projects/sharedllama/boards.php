<?php

require_once('../dbconf.php');
require "DBHandler.php";

$pdo = new PDO($driver, $user, $pass, $attr);
$handler = new DBHandler($pdo, "sharedllama");
$handler->init();

?>

<!doctype html>
<html>
<head>
	<link rel="icon" type="image/png" href="assets/img/favicon-32x32.png" sizes="32x32" />
	<link rel="icon" type="image/png" href="assets/img/favicon-16x16.png" sizes="16x16" />
	<meta charset="UTF-8">
	<link type="text/css" rel="stylesheet" href="assets/css/style.css">
	<title>Shared Llama Boards</title>
</head>

<body>

<header>
	<img src="assets/img/llama.png">
</header>

<nav>
	<ul>
		<li><a href="index.php">Home</a></li>
		<li><a class="selected" href="boards.php">Boards</a></li>
		<li><a href="about.html">About</a></li>
		<li><a href="login.php">Login</a></li>
	</ul>
</nav>

<div class="container">

    <?php

    $data = $handler->getBoards();
    foreach ($data as $row) {
        $name = $row;
        echo "<li class='board'><a href='stories?board=$name'><h2>$name</h2></a></li>";
    }

    ?>

</div>

<footer>
	<p>You got to the footer</p>
</footer>

</body>

</html>
