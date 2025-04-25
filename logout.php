<?php
session_start();

// Rensa sessionsdata
$_SESSION = array();

// Förstör sessionen
session_destroy();

// Omdirigera till inloggningssidan
header("Location: login.php");
exit();
?>