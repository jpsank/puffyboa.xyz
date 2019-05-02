<?php

include "DBHandler.php";

$db_folder = "../db";

$handler = new DBHandler("$db_folder/apedia.db");
$handler->init();

session_start();

?>

<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="../assets/css/shared.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>APedia - puffyboa.xyz</title>
</head>

<body>

<div class="back-to-home">
    <a href="../index.html">Home</a>
</div>

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

    function createSubmitForm($id, $text, $block) {
        $block = $block ? "block": "";
        if ($_SESSION["loggedin"] === true) {
            echo "<form onfocusout='focusOut(this)' onfocusin='focusIn(this)' class='submit $block' method='post'>
<textarea name='text' placeholder='$text' required></textarea>
<input type='hidden' name='parent_id' value=$id>
<input type='submit'>
</form>";
        } else {
            echo "<p class='login_link'><a href='login.php'>Login to <span>$text</span></a></p>";
        }
    }

    function displayPost($post_arr) {
        global $handler;
        $id = $post_arr["id"];
        $type = $post_arr["type"];
        $text = $post_arr["text"];

        $post_user = $handler->fetchUserById($post_arr["post_user"]);
        $username = $post_user["username"];
        $uid = $post_user["id"];

        echo "<div class='post $type' id='$id'>";
        $handler->createVoteContainerHTML($post_arr);
        echo "<p class='post_user'><a href='user.php?id=$uid'>$username</a></p><p class='text'>$text</p>";
        switch ($type) {
            case "answer":
                createSubmitForm($id, "Comment", true);
                break;
            case "comment":
                createSubmitForm($id, "Reply", true);
                break;
        }
        echo "</div>";
    }
    function unloadComments($post_arr) {
        global $handler;
        displayPost($post_arr);
        $id = $post_arr["id"];
        $children = $handler->fetchCommentsUnder($id);
        $children = $handler->sortPostsByVotes($children);
        if (!empty($children)) {
            echo "<ul>";
            foreach ($children as $c) {
                unloadComments($c);
            }
            echo "</ul>";
        }
    }

    if ($_GET["id"] == "") {
    } else {
        $question_id = (int)$_GET["id"];
        $q_arr = $handler->fetchPostById($question_id);

        if (isset($_POST["vote_id"])) {
            if ($_SESSION["loggedin"] === true) {
                $vote_id = $_POST["vote_id"];
                $handler->toggleVoteOn($vote_id, $_SESSION["id"]);
                header("location: question.php?id=$question_id#$vote_id");
            }
        }

        if (isset($_POST["text"]) && isset($_POST["parent_id"])) {
            $text = htmlspecialchars($_POST["text"]);
            $parent_id = $_POST["parent_id"];
            $parent_arr = $handler->fetchPostById($parent_id);
            switch ($parent_arr["type"]) {
                case "question":
                    $handler->insertAnswer($text, $parent_id, $_SESSION["id"]);
                    $last_id = $handler->lastInsertRowID();
                    header("Refresh:0; url=question.php?id=$question_id#$last_id");
                    break;
                case "answer":
                    $handler->insertComment($text, $parent_id, $_SESSION["id"]);
                    $last_id = $handler->lastInsertRowID();
                    header("Refresh:0; url=question.php?id=$question_id#$last_id");
                    break;
                case "comment":
                    $handler->insertComment($text, $parent_id, $_SESSION["id"]);
                    $last_id = $handler->lastInsertRowID();
                    header("Refresh:0; url=question.php?id=$question_id#$last_id");
                    break;
            }
        }


        if ($q_arr) {
            $text = $q_arr["text"];

            $t_arr = $handler->fetchTopicById($q_arr["parent"]);
            $topic_id = $t_arr["id"];
            $name = $t_arr["name"];

            echo "<div class='question_header'>";
            echo "<h3><a href='topic.php?id=$topic_id'>$name</a></h3>";
            echo "<div class='question_title' id='$question_id'>";
            echo "<h1>$text";
            $handler->createVoteContainerHTML($q_arr);
            echo "</h1>";
            createSubmitForm($question_id, "Add an answer", true);
            echo "</div>";
            echo "</div>";

            $answers = $handler->fetchAnswersToQuestion($question_id);
            $answers = $handler->sortPostsByVotes($answers);

            $len_answers = sizeof($answers);
            $s = $len_answers==1 ? "Answer": "Answers";
            echo "<h3 class='num_answers'>$len_answers $s</h3>";

            echo "<div class='answers_container'>";

            foreach ($answers as $answer) {
                unloadComments($answer);
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
