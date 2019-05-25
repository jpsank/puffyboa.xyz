<?php

class DBHandler {
    private $db;
    private $per_page_limit = 50;

    function __construct($fp) {
        $this->db = new SQLite3($fp);
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

    // Everything else

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
		post_date TIMESTAMP,
		has_attachment bit NOT NULL DEFAULT (0)
		)";
        $this->db->exec($sql);

    }

    function display($page=0) {
        $offset = $page*$this->per_page_limit;

        // Fetch messages from database
        $result = $this->selectBySQL("Messages", "ORDER BY post_date DESC LIMIT $this->per_page_limit OFFSET $offset");
        $data = iterator_to_array($this->fetchResultArrays($result));

        foreach ($data as $idx => $val) {
            $id = $val["id"];
            $message = htmlspecialchars($val["message"]);
            $post_date = strtotime($val["post_date"]);
            $has_attachment = $val["has_attachment"];

            $t = time()-$post_date;
            $u = "seconds";
            if ($t > 60) {
                $t = round($t/60);
                $u = "minutes";

                if ($t > 60) {
                    $t = round($t/60);
                    $u = "hours";

                    if ($t > 24) {
                        $t = round($t/24);
                        $u = "days";
                    }
                }
            }
            $html = "<div id='$id' class='message'>";
            $html .= "<p class='t'>$t $u ago</p>";
            $html .= "<p class='m'>$message</p>";
            if ($has_attachment) {
                $file_path = 'uploads/' . $id;

                $finfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file_path);
                $file_text = "";
                if (substr($finfo,0,4) == "text") {
                    $file_text = file_get_contents($file_path);
                }
                $html .= "<img src='$file_path' alt='$file_text'>";
            }
            $html .= "</div>";
            echo $html;
        }

        $num_rows = $this->countRows("Messages");
        $num_pages = ceil($num_rows / (float)$this->per_page_limit);
        echo "<div class='pages'>";

        $prev_adj = $page;
        if ($prev_adj > 0) {
            echo "<div><a href='?page=$prev_adj'>&lt;</a></div>";
        }

        for ($i=$page-5; $i<$page+6; $i++) {
            if ($i >= 0 && $i < $num_pages) {
                $i2 = $i + 1;
                if ($i == $page) {
                    echo "<div class='current'>$i2</div>";
                } else {
                    if ($i < $page) {
                        $class = "previous";
                    } else if ($i > $page) {
                        $class = "next";
                    }
                    echo "<div class='$class'><a href='?page=$i2'>$i2</a></div>";
                }
            }
        }

        $next_adj = $page+2;
        if ($next_adj <= $num_pages) {
            echo "<div><a href='?page=$next_adj'>&gt;</a></div>";
        }
        echo "</div>";
    }

    function postMessage() {
        $text = $_POST['text'];
        $time = strftime("%Y-%m-%d %H:%M:%S",time());
        if ($_FILES["userfile"]["error"] == UPLOAD_ERR_NO_FILE) {
            $attached = 0;
        } else {
            $attached = 1;
        }

        $sql = "INSERT INTO Messages (message, post_date, has_attachment) VALUES (:message,:post_date,:attached);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':message', $text);
        $stmt->bindParam(':post_date', $time);
        $stmt->bindParam(':attached', $attached);
        $stmt->execute();

        if ($attached) {
            if ($_FILES["userfile"]["error"] == UPLOAD_ERR_INI_SIZE) {
                $error = "Image upload error: File too large";
            } else if ($_FILES["userfile"]["error"] != 0) {
                $error = "Image upload error: Code " . $_FILES["userfile"]["error"];
            }

            $last_id = $this->db->lastInsertId();
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