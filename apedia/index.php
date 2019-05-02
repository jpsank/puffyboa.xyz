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
    if (isset($_SESSION["loggedin"]) && $_SESSION(["loggedin"]) === true) {
        $username = $_SESSION["username"];
        $id = $_SESSION["id"];
        echo "<li>Logged in as <a href='user.php?id=$id'>$username</a>. <a href='logout.php'>Log out</a></li>";
    } else {
        echo "<li><a href='login.php'>Log in</a> or <a href='register.php'>Sign up</a></li>";
    }
    ?>
</ul>

<div class="jumbo title">
    <h1>
        <a href="index.php"><span class="A">A</span><span class="P">P</span>edia</a>
    </h1>
    <p>Crowd-sourced Q and A for AP classes</p>
</div>

<form id="search" method="get">
    <input type="text" name="search" placeholder="Search questions"
           value="<?php echo isset($_GET["search"]) ? htmlspecialchars($_GET['search']) : ""; ?>">
    <input type="submit" value="Search">
</form>


<div class="main">
    <?php

    function constrainString($str) {
        if (strlen($str) > 100) {
            return substr($str, 0, 100) . "...";
        }
        return $str;
    }

    function displayResult($arr) {
        global $handler;
        $id = $arr["id"];
        switch ($arr["type"]) {
            case "topic":
                $name = $arr["name"];
                echo "<div class='result'><a class='topic' href='topic.php?id=$id'>$name</a></div>";
                break;
            default:
                $parentList = $handler->findPostParentList($id);
                array_push($parentList, $arr);
                $html = "";
                foreach ($parentList as $i=>$parent) {
                    $pid = $parent["id"];
                    if ($i == 0) {
                        $name = $parent["name"];
                        $html .= "<a class='topic' href='topic.php?id=$pid'>$name</a>";
                    } else if ($i == 1) {
                        $text = constrainString($parent["text"]);
                        $html .= "<a class='question' href='question.php?id=$pid'>$text</a>";
                    } else if ($i == 2) {
                        $qid = $parentList[1]["id"];
                        $text = constrainString($parent["text"]);
                        $html .= "<a class='answer' href='question.php?id=$qid#$pid'>$text</a>";
                    } else {
                        $qid = $parentList[1]["id"];
                        $text = constrainString($parent["text"]);
                        $html .= "<a class='comment' href='question.php?id=$qid#$pid'>$text</a>";
                    }
                }
                echo "<div class='result'>$html</div>";
        }
    }

    if (isset($_GET["search"])) {
        echo "<div class='search_results'>";
        $search = htmlspecialchars($_GET["search"]);
        $results = $handler->searchPosts($search);
        if (empty($results)) {
            echo "<p>No results found.</p>";
        } else {
            foreach ($results as $arr) {
                displayResult($arr);
            }
        }
        echo "</div>";
    } else {
        echo "<div class='topics_container'>";
        $results = $handler->selectTopicsBySQL(null);
        while ($arr = $results->fetchArray(SQLITE3_ASSOC)) {
            $id = $arr["id"];
            $name = $arr["name"];
            echo "<div><a href='topic.php?id=$id'>$name</a></div>";
        }
        echo "</div>";
    }

    ?>
</div>

</body>
</html>