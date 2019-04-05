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

    function createSubmitForm($id, $text, $block) {
        $block = $block ? "block": "";
        echo "<form onfocusout='focusOut(this)' onfocusin='focusIn(this)' class='submit $block' method='post'>
<textarea name='text' placeholder='$text' required></textarea>
<input type='hidden' name='parent_id' value=$id>
<input type='submit'>
</form>";
    }

    function displayPost($post_arr) {
        global $handler;
        $id = $post_arr["id"];
        $type = $post_arr["type"];
        $text = $post_arr["data"]->text;

        $uid = $post_arr["data"]->post_user;
        $username = $handler->getEntityDataById($uid)->username;

        echo "<div class='post $type' id='$id'>";
        echo "<p>$text</p><p>$username</p>";
        switch ($type) {
            case "question":
                createSubmitForm($id, "Add an answer", false);
                break;
            case "answer":
                createSubmitForm($id, "Comment", false);
                break;
            case "comment":
                createSubmitForm($id, "Reply", false);
                break;
        }
        echo "</div>";
    }
    function unloadChildren($post_arr) {
        global $handler;
        displayPost($post_arr);
        $id = $post_arr["id"];
        $children = $handler->fetchChildren($id);
        if (!empty($children)) {
            echo "<ul>";
            foreach ($children as $c) {
                unloadChildren($c);
            }
            echo "</ul>";
        }
    }

    if ($_GET["id"] == "") {
    } else {
        $question_id = (int)$_GET["id"];
        $q_arr = $handler->fetchEntityById($question_id);


        if (isset($_POST["text"]) && isset($_POST["parent_id"])) {
            $text = $_POST["text"];
            $parent_id = $_POST["parent_id"];
            $parent_arr = $handler->selectEntityById($parent_id);
            switch ($parent_arr["type"]) {
                case "question":
                    $handler->insertAnswer($text, $parent_id, 1);
                    $last_id = $handler->lastInsertRowID();
                    header("Refresh:0; url=question.php?id=$question_id#$last_id");
                    break;
                case "answer":
                    $handler->insertComment($text, $parent_id, 1);
                    $last_id = $handler->lastInsertRowID();
                    header("Refresh:0; url=question.php?id=$question_id#$last_id");
                    break;
                case "comment":
                    $handler->insertComment($text, $parent_id, 1);
                    $last_id = $handler->lastInsertRowID();
                    header("Refresh:0; url=question.php?id=$question_id#$last_id");
                    break;
            }
        }


        if ($q_arr) {
            $text = $q_arr["data"]->text;
            $t_arr = $handler->fetchEntityById($q_arr["data"]->parent);
            $topic_id = $t_arr["id"];
            $name = $t_arr["data"]->name;
            echo "<div class='question_header'>";
            echo "<h3><a href='topic.php?id=$topic_id'>$name</a></h3>";
            echo "<div class='question_title' id='$question_id'>";
            echo "<h1>$text</h1>";
            createSubmitForm($question_id, "Add an answer", true);
            echo "</div>";
            echo "</div>";

            $children = $handler->fetchChildren($question_id);

            $len_children = sizeof($children);
            $s = $len_children==1 ? "Answer": "Answers";
            echo "<h3 class='num_answers'>$len_children $s</h3>";

            echo "<div class='answers_container'>";

            foreach ($children as $child) {
                unloadChildren($child);
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
