<?php

if (isset($_POST["name"]) && isset($_POST["email"]) && isset($_POST["message"])) {
    $recipient = "julian@sankergroup.org";

    $name = $_POST["name"];
    $email = $_POST["email"];
    $message = $_POST["message"];

    $subject = "New form submission on puffyboa.xyz";
    $mailBody = "Name: $name\nEmail: $email\n\n$message";

    $good = mail($recipient, $subject, $mailBody, "From: $name <$email>");

    if ($good == false) {
        echo "<p>Message did not send.</p>";
    } else {
        echo "<p>Thank you! Your message has been sent.</p>";
        header("Location: index.html");
    }
}
