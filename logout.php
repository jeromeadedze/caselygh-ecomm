<?php
require_once __DIR__ . "/core/core.php";

secure_logout();
$_SESSION['success'] = "You're logged out. See you soon!";
header("Location: index.php");
exit;
