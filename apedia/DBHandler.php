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
        // Create table if not already created
        $sql = "CREATE TABLE IF NOT EXISTS Entities (
		id INTEGER PRIMARY KEY,
		type TEXT NOT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		data LONGTEXT NOT NULL
		)";
        $this->db->exec($sql);

//        $sql = "CREATE TABLE IF NOT EXISTS Topics (
//		id INTEGER PRIMARY KEY,
//		name TEXT NOT NULL
//		)";
//        $this->db->exec($sql);
//        $sql = "CREATE TABLE IF NOT EXISTS Posts (
//		id INTEGER PRIMARY KEY,
//		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//		type TEXT NOT NULL,
//		text TEXT NOT NULL,
//		parent INTEGER NOT NULL,
//		post_user INTEGER NOT NULL
//		)";
//        $this->db->exec($sql);
//        $sql = "CREATE TABLE IF NOT EXISTS Users (
//		id INTEGER PRIMARY KEY,
//		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//		username VARCHAR(50) NOT NULL UNIQUE,
//		password VARCHAR(255) NOT NULL
//		)";
//        $this->db->exec($sql);
//        $sql = "CREATE TABLE IF NOT EXISTS Votes (
//		id INTEGER PRIMARY KEY,
//		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//		post_id INTEGER NOT NULL,
//		user_id INTEGER NOT NULL
//		)";
//        $this->db->exec($sql);

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
            $check = $this->fetchEntities("topic",["name"=>$t]);
            if ($check === []) {
                $this->insertTopic($t);
            }
        }
    }

    function insertEntity($type, $data) {
        $encoded = json_encode($data);

        $sql = "INSERT INTO Entities (type, data) VALUES (:type, :data);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':data', $encoded);
        return $stmt->execute();
    }

    function insertTopic($name) {
        $data = [
            "name" => $name,
        ];
        return $this->insertEntity("topic", $data);
    }
    function insertQuestion($text, $topic_id, $post_user_id) {
        $data = [
            "text" => $text,
            "parent" => $topic_id,
            "post_user" => $post_user_id,
        ];
        return $this->insertEntity("question",$data);
    }
    function insertAnswer($text, $question_id, $post_user_id) {
        $data = [
            "text" => $text,
            "parent" => $question_id,
            "post_user" => $post_user_id,
        ];
        return $this->insertEntity("answer",$data);
    }
    function insertComment($text, $parent_id, $post_user_id) {
        $data = [
            "text" => $text,
            "parent" => $parent_id,
            "post_user" => $post_user_id,
        ];
        return $this->insertEntity("comment",$data);
    }

    function insertUser($username, $password) {
        $data = [
            "username" => $username,
            "password" => $password,
        ];
        return $this->insertEntity("user",$data);
    }
    function insertVote($post_id, $user_id) {
        $data = [
            "post_id" => $post_id,  // id of post they upvoted
            "user" => $user_id,  // who voted
        ];
        return $this->insertEntity("vote",$data);
    }

    function selectEntityById($id) {
        $sql = "SELECT * FROM Entities WHERE id=$id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();
        return $result->fetchArray();
    }
    function fetchEntityById($id) {
        $arr = $this->selectEntityById($id);
        $arr["data"] = json_decode($arr["data"]);
        return $arr;
    }
    function getEntityDataById($id) {
        return json_decode($this->selectEntityById($id)["data"]);
    }
    function returnEntityParentList($id) {
        $entity = $this->selectEntityById($id);
        $entity["data"] = json_decode($entity["data"]);
        $parents = [];
        while ($entity["data"]->parent) {
            $entity = $this->selectEntityById($entity["data"]->parent);
            $entity["data"] = json_decode($entity["data"]);
            array_push($parents,$entity);
        }
        $parents = array_reverse($parents);
        return $parents;
    }

    function selectEntitiesByType($type) {
        if (is_array($type)) {  // match multiple types
            $sql = "SELECT * FROM Entities WHERE type='$type[0]'";
            foreach (array_slice($type,1) as $t) {
                $sql .= " OR type='$t'";
            }
        } else if ($type === null) {  // no type specified, match all types
            $sql = "SELECT * FROM Entities";
        } else {  // match for one type
            $sql = "SELECT * FROM Entities WHERE type='$type'";
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }

    function searchPosts($search) {  // search questions
        $search = strtolower($search);
        $result = $this->selectEntitiesByType(["question", "topic"]);
        $final = [];
        while($arr = $result->fetchArray(SQLITE3_ASSOC)) {
            $arr["data"] = json_decode($arr["data"]);
            switch ($arr["type"]) {
                case "topic":
                    $name = strtolower($arr["data"]->name);
                    if (strpos($name, $search) !== false) {
                        similar_text($search, $name, $arr["score"]);
                        $final[$arr["id"]] = $arr;
                    }
                    break;
                default:
                    $text = strtolower($arr["data"]->text);
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

    function fetchEntities($type, $match_data) {
        $result = $this->selectEntitiesByType($type);

        $final = [];
        while($arr = $result->fetchArray(SQLITE3_ASSOC)) {
            $arr["data"] = json_decode($arr["data"]);
            if (!array_diff($match_data, (array)$arr["data"])) {  // if contains all values
                array_push($final, $arr);
            }
        }
        return $final;
    }
    function fetchQuestionsByTopic($topic_id) {
        return $this->fetchEntities("question",["parent"=>$topic_id]);
    }
    function fetchQuestionAnswers($question_id) {
        return $this->fetchEntities("answer",["parent"=>$question_id]);
    }
    function fetchComments($parent_id) {
        return $this->fetchEntities("comment",["parent"=>$parent_id]);
    }
    function fetchVotes($post_id) {
        return $this->fetchEntities("vote",["post_id"=>$post_id]);
    }
    function fetchChildren($parent_id) {
        return $this->fetchEntities(null,["parent"=>$parent_id]);
    }

    function postQuestion($text, $topic_name, $post_user_id) {
        $matches = $this->fetchEntities("topic", ["name" => $topic_name]);
        $topic_id = $matches[0]["id"];

        $this->insertQuestion($text, $topic_id, $post_user_id);
    }

    function lastInsertRowID() {
        return $this->db->lastInsertRowID();
    }

    function close() {
        $this->db = null;
    }

}