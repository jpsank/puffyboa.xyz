<?php
include 'DBHandler.php';

if (isset($_GET["last_id"])) {
    $new_messages = $handler->fetchMessagesAfter($_GET["last_id"]);
    echo json_encode($new_messages);
}
