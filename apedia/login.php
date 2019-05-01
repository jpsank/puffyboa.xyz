<?php

include "DBHandler.php";

$db_folder = "../db";

$handler = new DBHandler("$db_folder/apedia.db");
$handler->init();

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

if (!empty($_POST["username"]) && !empty($_POST["password"])) {
    $username = htmlspecialchars(trim($_POST["username"]));
    $password = trim($_POST["password"]);
    $user_arr = $handler->fetchUserByName($username);
    if (empty($user_arr)) {
        $err_message = "Invalid username.";
    } else {  // user found
        $pass_check = password_verify($password, $user_arr["password"]);
        if ($pass_check) {  // password matches
            session_start();
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $user_arr["id"];
            $_SESSION["username"] = htmlspecialchars($user_arr["username"]);

            header("location: index.php");
        } else {
            $err_message = "Invalid password.";
        }
    }
}

?>

<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>APedia - puffyboa.xyz</title>
</head>

<body>

<div class="left title">
    <a href="index.php">
        <h1><span class="A">A</span><span class="P">P</span>edia</h1>
    </a>
</div>

<div id="main">
    <h1>Login</h1>
    <p>Please fill in your credentials to log in</p>
    <p class="err_message"><?php echo isset($err_message) ? $err_message : ""; ?></p>
    <form id='login' method='post'>
        <label for="username">Username</label>
        <input type='text' name='username' id="username" required>
        <label for="password">Password</label>
        <input type='password' name='password' id="password" required>
        <input type='submit' value="Login">
    </form>
    <p>Don't have an account? <a href="register.php">Sign up now.</a></p>
</div>



</body>
</html>