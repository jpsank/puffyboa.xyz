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

    function constrainString($str) {
        if (strlen($str) > 100) {
            return substr($str, 0, 100) . "...";
        }
        return $str;
    }

    function displayPost($arr) {
        global $handler;
        $id = $arr["id"];
        $final_type = $arr["type"];

        $parentList = $handler->findPostParentList($id);
        array_push($parentList, $arr);
        $html = "";
        foreach ($parentList as $i=>$parent) {
            $pid = $parent["id"];
            if ($i == sizeof($parentList)-1) {
                $class = "final";
            }
            if ($i == 0) {
                $name = $parent["name"];
                $html .= "<a class='topic $class' href='topic.php?id=$pid'>$name</a>";
            } else if ($i == 1) {
                $text = constrainString($parent["text"]);
                $html .= "<a class='question $class' href='question.php?id=$pid'>$text</a>";
            } else if ($i == 2) {
                $qid = $parentList[1]["id"];
                $text = constrainString($parent["text"]);
                $html .= "<a class='answer $class' href='question.php?id=$qid#$pid'>$text</a>";
            } else {
                $qid = $parentList[1]["id"];
                $text = constrainString($parent["text"]);
                $html .= "<a class='comment $class' href='question.php?id=$qid#$pid'>$text</a>";
            }
        }
        echo "<div class='user_post $final_type'>$html</div>";
    }

    if ($_GET["id"] == "") {
    } else {
        $uid = (int)$_GET["id"];
        $u_arr = $handler->fetchUserById($uid);

        if ($u_arr) {
            $username = $u_arr["username"];
            $created_at = $u_arr["created_at"];

            echo "<h1>$username</h1>";
            echo "<p>Joined $created_at</p>";

            $score = $handler->countUserScore($uid);
            echo "<p>User score: $score</p>";

            $posts = $handler->fetchPostsByUser($uid);

            $len_posts = sizeof($posts);
            $s = $len_posts==1 ? "Post": "Posts";
            echo "<h3 class='num_posts'>$len_posts $s</h3>";

            echo "<div class='posts_container'>";

            foreach ($posts as $post) {
                displayPost($post);
            }

            echo "</div>";

        }

    }

    ?>
</div>


</body>
</html>
