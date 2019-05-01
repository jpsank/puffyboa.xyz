<?php

include "DBHandler.php";

$db_folder = "../db";

$handler = new DBHandler("$db_folder/apedia.db");
$handler->init();

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

if (!empty($_POST["username"]) && !empty($_POST["password"]) && !empty($_POST["password_confirm"])) {
    $username = htmlspecialchars(trim($_POST["username"]));
    $password = trim($_POST["password"]);
    $password_confirm = trim($_POST["password_confirm"]);

    if (strlen($password) < 6) {
        $err_message = "Password must have at least 6 characters.";
    } else { // password is valid

        if ($password !== $password_confirm) {
            $err_message = "Passwords do not match.";
        } else {  // password matches confirm password
            $check = $handler->fetchUserByName($username);
            if (!empty($check)) {
                $err_message = "This username is already taken.";
            } else {  // username not already taken
                $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                if ($handler->insertUser($username, $pass_hash)) {
                    header("location: login.php");
                } else {
                    $err_message = "Something went wrong. Please try again later.";
                }
            }
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
    <h1>Sign up</h1>
    <p>Please fill this form to create an account</p>
    <p class="err_message"><?php echo $err_message; ?></p>
    <form id='login' method='post'>
        <label for="username">Username</label>
        <input type='text' name='username' id="username" required value="<?php echo $_POST['username']; ?>">
        <label for="password">Password</label>
        <input type='password' name='password' id="password" required value="<?php echo $_POST['password']; ?>">
        <label for="password_confirm">Confirm Password</label>
        <input type='password' name='password_confirm' id="password_confirm" required value="<?php echo $_POST['password_confirm']; ?>">
        <input type='submit'>
    </form>
    <p>Already have an account? <a href="login.php">Login here.</a></p>
</div>



</body>
</html>