<?php
include 'DBHandler.php';

if (isset($_GET["last_id"])) {
    $new_messages = $handler->fetchMessagesAfter($_GET["last_id"]);
    echo json_encode($new_messages);
} else if (isset($_GET["page"])) {
    if (isset($_GET["per_page"])) {
        $result = $handler->fetchMessagesInPage($_GET["page"], $_GET["per_page"]);
    } else {
        $result = $handler->fetchMessagesInPage($_GET["page"]);
    }
    echo json_encode($result);
}
