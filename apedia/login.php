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


