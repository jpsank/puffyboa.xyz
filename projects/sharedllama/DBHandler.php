<?php

class DBHandler {
    public $conn;
    public $dbname;
    public $data;

    function __construct($conn, $dbname) {
        $this->conn = $conn;
        $this->dbname = $dbname;
    }

    function init() {

        // Create database if not already created

        $sql = "CREATE DATABASE IF NOT EXISTS " . $this->dbname;
        $this->conn->exec($sql);
        $sql = "USE " . $this->dbname;
        $this->conn->exec($sql);

        // Create tables if not already created

        $sql = "CREATE TABLE IF NOT EXISTS Accounts (
		username TEXT NOT NULL,
		password TEXT NOT NULL,
		join_date TIMESTAMP
		)";
        $this->conn->exec($sql);

        $sql = "CREATE TABLE IF NOT EXISTS Stories (
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		board TEXT NOT NULL,
		title TEXT NOT NULL,
		text LONGTEXT NOT NULL,
		post_user TEXT NOT NULL,
		post_date TIMESTAMP
		)";
        $this->conn->exec($sql);
    }

    function getTimestamp() {
        $time = strftime("%Y-%m-%d %H:%M:%S",time());
        return $time;
    }

    function fetchQuery($sql) {
        $stmt = $this->conn->prepare($sql);
        $data = array();
        if ($stmt->execute()) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    function getAccount($user) {
        $sql = "SELECT * FROM Accounts WHERE username=$user;";
        $data = $this->fetchQuery($sql);
        return $data;
    }

    function createAccount($user, $pass) {
        $user = $this->conn->quote($user);
        $pass = $this->conn->quote($pass);
        $time = $this->getTimestamp();

        if ($this->getAccount($user)) {
            return false;
        }

        $sql = "INSERT INTO Accounts (username, password, join_date) VALUES ($user, $pass,'$time');";
        $this->conn->exec($sql);

        return true;
    }

    function getBoards() {
        $sql = "SELECT DISTINCT (board) FROM Stories;";
        $data = $this->fetchQuery($sql);
        return $data;
    }

    function searchAllStories($query) {
        $sql = "SELECT * FROM Stories WHERE title LIKE '%$query%';";
        $data = $this->fetchQuery($sql);
        return $data;
    }
    function searchStories($board, $query) {
        $sql = "SELECT * FROM Stories WHERE board='$board' AND title LIKE '%$query%';";
        $data = $this->fetchQuery($sql);
        return $data;
    }

    function postStory($board, $title, $text, $user) {

        $board = $this->conn->quote($board);
        $title = $this->conn->quote($title);
        $text = $this->conn->quote($text);
        $user = $this->conn->quote($user);
        $time = $this->getTimestamp();

        $sql = "INSERT INTO Stories (board, title, text, post_user, post_date) VALUES ($board, $title, $text, $user, '$time');";
        $this->conn->exec($sql);

        return true;

    }

    function close() {
        $this->conn = null;
    }

}

