<?php

class DBHandler {
    private $db;

    function __construct($fp) {
        $this->db = new SQLite3($fp);
    }

    function init() {
        if (!file_exists("uploads")) {
            $oldmask = umask(0);
            mkdir("uploads", 0777);
            umask($oldmask);
        }

        // Create table if not already created

        $sql = "CREATE TABLE IF NOT EXISTS Messages (
		id INTEGER PRIMARY KEY,
		message VARCHAR(2000) NOT NULL,
		post_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		has_attachment bit NOT NULL DEFAULT (0)
		)";
        $this->db->exec($sql);

    }

    // Low-level select function

    function selectBySQL($table, $sql) {
        if (!empty($sql)) {
            $sql = "SELECT * FROM $table $sql";
        } else {
            $sql = "SELECT * FROM $table";
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }

    // Mid-level fetch functions

    function countRows($table) {
        $sql = "SELECT COUNT(*) FROM $table";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute()->fetchArray()[0];
    }

    private function fetchResultArrays(SQLite3Result $result) {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            yield $row;
        }
    }

    function fetchMessagesInPage($page, $per_page=50) {
        $offset = $page*$per_page;

        // Fetch messages from database
        $result = $this->selectBySQL("Messages", "ORDER BY post_date DESC LIMIT $per_page OFFSET $offset");
        $data = iterator_to_array($this->fetchResultArrays($result));

        $num_rows = $this->countRows("Messages");
        $num_pages = ceil($num_rows / (float)$per_page);

        $data = [
            "metadata"=>["num_pages"=>$num_pages, "num_messages"=>$num_rows],
            "result"=>$data];
        return $data;
    }

    function fetchMessagesAfter($id) {
        $result = $this->selectBySQL("Messages", "WHERE id > $id ORDER BY post_date DESC");
        $data = iterator_to_array($this->fetchResultArrays($result));
        $data = [
            "metadata"=>["num_messages"=>sizeof($data)],
            "result"=>$data
        ];
        return $data;
    }

    // High-level functions

    function postMessage() {
        $text = $_POST['text'];
        if ($_FILES["userfile"]["error"] == UPLOAD_ERR_NO_FILE) {
            $attached = 0;
        } else {
            $attached = 1;
        }

        $sql = "INSERT INTO Messages (message, has_attachment) VALUES (:message,:attached);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':message', $text);
        $stmt->bindParam(':attached', $attached);
        $stmt->execute();

        if ($attached) {
            if ($_FILES["userfile"]["error"] == UPLOAD_ERR_INI_SIZE) {
                $error = "Image upload error: File too large";
            } else if ($_FILES["userfile"]["error"] != 0) {
                $error = "Image upload error: Code " . $_FILES["userfile"]["error"];
            }

            $last_id = $this->db->lastInsertRowID();
            $target_file = 'uploads/' . $last_id;

            if (isset($error)) {
                file_put_contents($target_file, $error);
            } else {
                $tmp_file = $_FILES["userfile"]["tmp_name"];
                store_uploaded_image($tmp_file, $target_file, 500);
            }
        }


    }

    function close() {
        $this->db = null;
    }

}

$handler = new DBHandler("../db/openchat.db");
$handler->init();