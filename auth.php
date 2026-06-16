<?php

require_once "config.php";
require_once "jwt.php";
require_once "response.php";

/* =========================
   GET PATH
========================= */
$method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$script = $_SERVER["SCRIPT_NAME"];
$path = str_replace($script, "", $uri);

/* =========================
   BODY
========================= */
$data = json_decode(file_get_contents("php://input"), true);

/* =========================
   SIGNUP
========================= */
if ($path === "/signup" && $method === "POST") {

    $name = $data["name"];
    $email = $data["email"];
    $password = password_hash($data["password"], PASSWORD_DEFAULT);

    /* =========================
       CHECK DUPLICATE EMAIL
    ========================= */
    $check = $conn->query("
        SELECT id FROM users WHERE email='$email' LIMIT 1
    ");

    if ($check->num_rows > 0) {
        respond(409, "error", "Email already exists");
    }

    /* =========================
       INSERT USER
    ========================= */
    $conn->query("
        INSERT INTO users(name,email,password)
        VALUES('$name','$email','$password')
    ");

    respond(201, "success", "User created successfully");
}

/* =========================
   LOGIN
========================= */
if ($path === "/login" && $method === "POST") {

    $email = $data["email"];
    $password = $data["password"];

    /* =========================
       AUTHENTICATE USER
    ========================= */
    $dbResult = $conn->query("
        SELECT * FROM users WHERE email='$email'
    ");

    $user = $dbResult->fetch_assoc();

    if (!$user || !password_verify($password, $user["password"])) {
        respond(401, "error", "Invalid credentials");
    }
    
    /* =========================
       ISSUE JWT
    ========================= */
    $token = createToken($user["id"], $secret, $jwt_algorithm, $jwt_issuer, $jwt_expiry);

    respond(200, "success", "Login successful", [
        "token" => $token
    ]);
}

/* =========================
   FALLBACK
========================= */

respond(404, "error", "Resource not found");