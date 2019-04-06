<?php

include "DBHandler.php";

$db_folder = "../db";

if (!file_exists($db_folder)) {
    $oldmask = umask(0);
    mkdir($db_folder, 0777);
    umask($oldmask);
}

$handler = new DBHandler("$db_folder/apedia.db");
$handler->init();

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
    <?php

    if ($_GET["id"] == "") {
    } else {
        $topic_id = (int)$_GET["id"];
        $arr = $handler->fetchTopicById($topic_id);
        if ($arr) {

            if (isset($_POST["text"])) {
                $text = $_POST["text"];
                $handler->insertQuestion($text, $topic_id, 1);
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
                echo "<div class='question' id='$qid'><p><a href='question.php?id=$qid'>$text</a></p><p>$username</p></div>";
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
