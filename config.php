<?php

/* =====================
   DATABASE CONNECTION
===================== */
$conn = new mysqli("localhost", "root", "", "project_middleware");

if ($conn->connect_error) {
    header("Content-Type: application/json");
    die(json_encode(["error" => "DB connection failed"]));
}

$conn->set_charset("utf8mb4");


/* =====================
   JWT CONFIG
===================== */

/* Load secret key from file */
$secret = file_get_contents("secret.key");

/* JWT settings */
$jwt_algorithm = "HS256";
$jwt_issuer = "project_middleware";   
$jwt_expiry = 900;     