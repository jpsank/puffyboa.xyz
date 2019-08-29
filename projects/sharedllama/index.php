<?php

require_once('../dbconf.php');
require "DBHandler.php";

$pdo = new PDO($driver, $user, $pass, $attr);
$handler = new DBHandler($pdo, "sharedllama");
$handler->init();

?>

<html lang="en">
<head>
    <link rel="shortcut icon" href="assets/img/favicon.png" />
    <meta charset="UTF-8">
    <link type="text/css" rel="stylesheet" href="assets/css/style.css">
    <title>Shared Llama</title>
</head>

<body>

<header>
    <img alt src="assets/img/llama.png">
</header>

<nav>
    <ul>
        <li><a class="selected" href="index.php">Home</a></li>
        <li><a href="boards.php">Boards</a></li>
        <li><a href="about.html">About</a></li>
        <li><a href="login.php">Login</a></li>
    </ul>
</nav>

<div id="boardlist">

    <form id="search_form" method="post">
        <input type="text" name="search" placeholder="search stories" maxlength="1000">
        <input type="submit">
    </form>

    <?php

    $data = $handler->searchAllStories($_POST["search"]);
    foreach ($data as $row) {
        $id = $row['id'];
        $board = $row['board'];
        $title = $row['title'];
        $text = $row['text'];
        $post_user = $row['post_user'];
        $post_date = $row['post_date'];
        echo "<div class='story'><a href='stories?id=$id'><h3>$title</h3><p>$text</p></a></div>";
    }

    ?>

</div>

<div class="parallax">

</div>

<footer>
    <p>You got to the footer</p>
</footer>

</body>

</html>

