<!doctype html>
<html>
<head>
	<link rel="icon" type="image/png" href="assets/img/favicon-32x32.png" sizes="32x32" />
	<link rel="icon" type="image/png" href="assets/img/favicon-16x16.png" sizes="16x16" />
	<meta charset="UTF-8">
	<link type="text/css" rel="stylesheet" href="assets/css/style.css">
	<title>Shared Llama Login</title>
</head>

<script>
	function login() {
		message = document.getElementById("message");
		message.innerHTML = "Incorrect username or password";
	}
</script>

<body>

<header>
	<img src="assets/img/llama.png">
</header>

<nav>
	<ul>
		<li><a href="index.php">Home</a></li>
		<li><a href="boards.php">Boards</a></li>
		<li><a href="about.html">About</a></li>
		<li><a class="selected" href="login.php">Login</a></li>
	</ul>
</nav>

<form id="login-form">
	<h1>Login now</h1>
	<input id="username" type="text" placeholder="username"/>
	<br>
	<input id="password" type="password" placeholder="password"/>
	<br>
	<button type="button" onclick="login()">login</button>
	<p id="message"></p>
</form>

<footer>
	<p>You got to the footer</p>
</footer>

</body>
</html>
