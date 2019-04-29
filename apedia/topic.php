<?php

include "DBHandler.php";

$db_folder = "../db";

$handler = new DBHandler("$db_folder/apedia.db");
$handler->init();

session_start();

?>

<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>APedia - puffyboa.xyz</title>
</head>

<body>

<ul class="nav">
    <?php
    if ($_SESSION["loggedin"] === true) {
        $username = $_SESSION["username"];
        $id = $_SESSION["id"];
        echo "<li>Logged in as <a href='user.php?id=$id'>$username</a>. <a href='logout.php'>Log out</a></li>";
    } else {
        echo "<li><a href='login.php'>Log in</a> or <a href='register.php'>Sign up</a></li>";
    }
    ?>
</ul>

<div class="left title">
    <h1><a href="index.php"><span class="A">A</span><span class="P">P</span>edia</a></h1>
</div>

<div id="main">
    <?php

    if ($_GET["id"] == "") {
    } else {
        $topic_id = (int)$_GET["id"];
        $arr = $handler->fetchTopicById($topic_id);
        if ($arr) {

            if (isset($_POST["vote_id"])) {
                if ($_SESSION["loggedin"] === true) {
                    $vote_id = $_POST["vote_id"];
                    $handler->toggleVoteOn($vote_id, $_SESSION["id"]);
                    header("location: topic.php?id=$topic_id#$vote_id");
                }
            }

            if (isset($_POST["text"])) {
                $text = $_POST["text"];
                $handler->insertQuestion($text, $topic_id, $_SESSION["id"]);
                header("Refresh:0");
            }

            $name = $arr["name"];
            echo "<h1>$name</h1>";

            echo "<div class='topic' id='$topic_id'>";
            if ($_SESSION["loggedin"] === true) {
                echo "<form onfocusout='focusOut(this)' onfocusin='focusIn(this)' class='submit block' method='post'>
<textarea name='text' placeholder='Ask a question' required></textarea>
<input type='submit'>
</form>";
            } else {
                echo "<p class='login_link'><a href='login.php'>Login to <span>Ask a question</span></a></p>";
            }
            echo "</div>";

            $questions = $handler->fetchQuestionsByTopic($topic_id);
            $questions = $handler->sortPostsByVotes($questions);
            $len_questions = sizeof($questions);
            $s = $len_questions == 1 ? "Question" : "Questions";
            echo "<h3 class='num_questions'>$len_questions $s</h3>";

            echo "<div class='questions_container'>";
            foreach ($questions as $q_arr) {
                $qid = $q_arr["id"];
                $text = $q_arr["text"];
                $uid = $q_arr["post_user"];
                $post_user = $handler->fetchUserById($uid);
                $username = $post_user["username"];
                echo "<div class='question' id='$qid'>";
                $handler->createVoteContainerHTML($q_arr);
                echo "<p class='text'><a href='question.php?id=$qid'>$text</a></p>
<p class='post_user'><a href='user.php?id=$uid'>$username</a></p>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "Topic not found.";
        }

    }

    ?>
</div>


<script src="script.js"></script>

</body>
</html>
