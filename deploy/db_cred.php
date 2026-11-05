<?php
// Database login on Railway, from the MySQL service's variables.
// The image copies this over core/db_cred.php. It holds no secrets.
define("DB_HOST", getenv("MYSQLHOST") . ";port=" . (getenv("MYSQLPORT") ?: "3306"));
define("DB_USER", getenv("MYSQLUSER"));
define("DB_PASS", getenv("MYSQLPASSWORD"));
define("DB_NAME", getenv("MYSQLDATABASE"));
