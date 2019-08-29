<?php

require_once('../dbconf.php');
require "DBHandler.php";

$pdo = new PDO($driver, $user, $pass, $attr);
$handler = new DBHandler($pdo, "sharedllama");
$handler->init();

if (!empty($_POST["text"]) && !empty($_POST["title"])) {
    $handler->postStory("global", $_POST["title"], $_POST["text"], 'puffyboa');
}

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


<form id="post_form" method="post">
    <input type="text" name="title" placeholder="title">
    <input type="text" name="text" placeholder="type story here">
    <input type="submit">
</form>


<div class="parallax">

</div>

<footer>
    <p>You got to the footer</p>
</footer>

</body>

</html>


