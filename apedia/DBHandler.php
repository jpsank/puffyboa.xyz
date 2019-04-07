<?php

function generateToken($length = 20) {
    return bin2hex(random_bytes($length));
}

class DBHandler {
    private $db;

    function __construct($fp) {
        $this->db = new SQLite3($fp);;
    }

    function init() {
        // Create tables if not already created

        $sql = "CREATE TABLE IF NOT EXISTS Topics (
		id INTEGER PRIMARY KEY,
		name TEXT NOT NULL
		)";
        $this->db->exec($sql);
        $sql = "CREATE TABLE IF NOT EXISTS Posts (
		id INTEGER PRIMARY KEY,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		type TEXT NOT NULL,
		text TEXT NOT NULL,
		parent INTEGER NOT NULL,
		post_user INTEGER NOT NULL
		)";
        $this->db->exec($sql);
        $sql = "CREATE TABLE IF NOT EXISTS Users (
		id INTEGER PRIMARY KEY,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		username VARCHAR(50) NOT NULL UNIQUE,
		password VARCHAR(255) NOT NULL
		)";
        $this->db->exec($sql);
        $sql = "CREATE TABLE IF NOT EXISTS Votes (
		id INTEGER PRIMARY KEY,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		post_id INTEGER NOT NULL,
		user_id INTEGER NOT NULL
		)";
        $this->db->exec($sql);

        $this->initTopics();
    }
    function initTopics() {
        $topics = ["AP Art History", "AP Biology", "AP Calculus", "AP Chemistry", "AP Chinese",
            "AP Comparative Government & Politics", "AP Computer Science", "AP English", "AP Environmental Science",
            "AP European History", "AP French", "AP German", "AP Human Geography", "AP Italian", "AP Japanese",
            "AP Latin", "AP Macroeconomics", "AP Microeconomics", "AP Music Theory", "AP Physics", "AP Psychology",
            "AP Research", "AP Seminar", "AP Spanish", "AP Statistics", "AP Studio Art",
            "AP U.S. Government & Politics", "AP U.S. History", "AP World History"];
        foreach ($topics as $t) {
            $check = $this->selectTopicsBySQL("name='$t'")->fetchArray();
            if (empty($check)) {
                $this->insertTopic($t);
            }
        }
    }
    function lastInsertRowID() {
        return $this->db->lastInsertRowID();
    }



    // Low-level Insert functions

    function insertTopic($name) {
        $sql = "INSERT INTO Topics (name) VALUES (:name);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $name);
        return $stmt->execute();
    }
    function insertPost($type, $text, $parent, $post_user) {
        $sql = "INSERT INTO Posts (type, text, parent, post_user) VALUES (:type, :text, :parent, :post_user);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':text', $text);
        $stmt->bindParam(':parent', $parent);
        $stmt->bindParam(':post_user', $post_user);
        return $stmt->execute();
    }
    function insertQuestion($text, $topic_id, $post_user_id) {
        return $this->insertPost("question", $text, $topic_id, $post_user_id);
    }
    function insertAnswer($text, $question_id, $post_user_id) {
        return $this->insertPost("answer", $text, $question_id, $post_user_id);
    }
    function insertComment($text, $parent_id, $post_user_id) {
        return $this->insertPost("comment", $text, $parent_id, $post_user_id);
    }

    function insertUser($username, $password) {
        $sql = "INSERT INTO Users (username, password) VALUES (:username, :password);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        return $stmt->execute();
    }
    function insertVote($post_id, $user_id) {
        $sql = "INSERT INTO Votes (post_id, user_id) VALUES (:post_id, :user_id);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    // High-level Post functions

    function postQuestion($text, $topic_name, $post_user_id) {
        $matches = $this->selectTopicsBySQL("name=$topic_name");
        $topic_id = $matches[0]["id"];

        $this->insertQuestion($text, $topic_id, $post_user_id);
    }

    // Low-level Select functions

    function selectBySQL($table, $condition) {
        if (!empty($condition)) {
            $sql = "SELECT * FROM $table WHERE $condition";
        } else {
            $sql = "SELECT * FROM $table";
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }

    function selectTopicById($id) {
        return $this->selectBySQL("Topics","id=$id");
    }
    function selectPostById($id) {
        return $this->selectBySQL("Posts","id=$id");
    }
    function selectUserById($id) {
        return $this->selectBySQL("Users","id=$id");
    }
    function selectVoteById($id) {
        return $this->selectBySQL("Votes","id=$id");
    }

    function selectPostsByType($type) {
        if (is_array($type)) {  // match multiple types
            $cond = "type='" . implode("' OR type='",$type);
            return $this->selectBySQL("Posts", $cond);
        } else {  // match for one type
            return $this->selectBySQL("Posts", "type='$type'");
        }
    }

//    function selectByMatch($table, $match) {
//        if (!empty($match)) {
//            $list = array_map(function ($key, $val) { return "$key=$val"; }, $match);
//            $cond = implode(" OR ", $list);
//            return $this->selectBySQL($table, $cond);
//        } else {
//            return $this->selectBySQL($table, null);
//        }
//    }
    function selectTopicsBySQL($sql) {
        return $this->selectBySQL("Topics", $sql);
    }
    function selectPostsBySQL($sql) {
        return $this->selectBySQL("Posts", $sql);
    }
    function selectUsersBySQL($sql) {
        return $this->selectBySQL("Users", $sql);
    }
    function selectVotesBySQL($sql) {
        return $this->selectBySQL("Votes", $sql);
    }

    function selectQuestionsByTopic($topic_id) {
        return $this->selectPostsBySQL("type='question' AND parent=$topic_id");
    }
    function selectAnswersToQuestion($question_id) {
        return $this->selectPostsBySQL("type='answer' AND parent=$question_id");
    }
    function selectCommentsUnder($parent_id) {
        return $this->selectPostsBySQL("type='comment' AND parent=$parent_id");
    }
    function selectVotesOn($post_id) {
        return $this->selectVotesBySQL("post_id=$post_id");
    }

    function selectUserByName($username) {
        return $this->selectBySQL("Users","username='$username'");
    }

    // Mid-level Fetch functions

    function fetchTopicById($id) {
        return $this->selectTopicById($id)->fetchArray();
    }
    function fetchPostById($id) {
        return $this->selectPostById($id)->fetchArray();
    }
    function fetchUserById($id) {
        return $this->selectUserById($id)->fetchArray();
    }
    function fetchVoteById($id) {
        return $this->selectVoteById($id)->fetchArray();
    }

    private function fetchResultArrays(SQLite3Result $result) {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            yield $row;
        }
    }

    function fetchQuestionsByTopic($topic_id) {
        $result = $this->selectQuestionsByTopic($topic_id);
        return iterator_to_array($this->fetchResultArrays($result));
    }
    function fetchAnswersToQuestion($question_id) {
        $result = $this->selectAnswersToQuestion($question_id);
        return iterator_to_array($this->fetchResultArrays($result));
    }
    function fetchCommentsUnder($parent_id) {
        $result = $this->selectCommentsUnder($parent_id);
        return iterator_to_array($this->fetchResultArrays($result));
    }
    function fetchVotesOn($post_id) {
        $result = $this->selectVotesOn($post_id);
        return iterator_to_array($this->fetchResultArrays($result));
    }

    function fetchUserByName($username) {
        return $this->selectUserByName($username)->fetchArray();
    }

    // High-level Find functions

    function findPostParentList($id) {
        $current = $this->fetchPostById($id);
        $parents = [];
        while (array_key_exists("parent",$current)) {
            if ($current["type"] == "question") {
                $current = $this->fetchTopicById($current["parent"]);
            } else {
                $current = $this->fetchPostById($current["parent"]);
            }
            array_push($parents,$current);
        }
        $parents = array_reverse($parents);
        return $parents;
    }

    function searchPosts($search) {  // search function
        $search = strtolower($search);
        $result = $this->selectPostsByType("question");
        $final = [];
        while($arr = $result->fetchArray(SQLITE3_ASSOC)) {
            switch ($arr["type"]) {
                case "topic":
                    $name = strtolower($arr["name"]);
                    if (strpos($name, $search) !== false) {
                        similar_text($search, $name, $arr["score"]);
                        $final[$arr["id"]] = $arr;
                    }
                    break;
                default:
                    $text = strtolower($arr["text"]);
                    if (strpos($text, $search) !== false) {
                        similar_text($search, $text, $arr["score"]);
                        $final[$arr["id"]] = $arr;
                    }
            }
        }
        $order = array_map(function ($val) { return -$val["score"]; }, array_values($final));
        array_multisort($order,$final);
        return $final;
    }



    function close() {
        $this->db = null;
    }

}