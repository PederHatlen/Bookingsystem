<?php
    define("IS_INCLUDED", TRUE);
    include 'phpRepo.php';
    logoff();
    var_dump($_SESSION);
    header('Location: booking.php');

    // Most complicated page ik it's hard to understand
?>