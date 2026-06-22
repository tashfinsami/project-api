<?php

/* =====================
   GLOBAL SETTINGS
===================== */

date_default_timezone_set('UTC');

/* =====================
   DATABASE CONNECTION
===================== */

$conn = new mysqli("127.0.0.1", "root", "", "project_middleware");

if ($conn->connect_error) {
   respond(500, "error", "Unable to connect to the database");
   exit;
}

$conn->set_charset("utf8mb4");

$conn->query("SET time_zone = '+00:00'");


/* =====================
   JWT CONFIG
===================== */

/* Load secret key from file */
$secret = file_get_contents("secret.key");

/* JWT settings */
$jwt_algorithm = "HS256";
$jwt_issuer = "project_middleware";   
$jwt_expiry = 900;     

/* =====================
   REDIS CONNECTION
===================== */
$redis = new Redis();
$redis->connect("127.0.0.1", 6379);