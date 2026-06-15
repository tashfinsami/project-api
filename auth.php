<?php

header("Content-Type: application/json");

require_once "config.php";
require_once "jwt.php";

function respond($data) {
    echo json_encode($data);
    exit;
}

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
   SIGNUP (PUBLIC)
========================= */
if ($path === "/signup" && $method === "POST") {

    $name = $data["name"];
    $email = $data["email"];
    $password = password_hash($data["password"], PASSWORD_DEFAULT);

    $conn->query("
        INSERT INTO users(name,email,password)
        VALUES('$name','$email','$password')
    ");

    respond(["message" => "Signup successful"]);
}

/* =========================
   LOGIN (ISSUE JWT)
========================= */
if ($path === "/login" && $method === "POST") {

    $email = $data["email"];
    $password = $data["password"];

    $result = $conn->query("
        SELECT * FROM users WHERE email='$email'
    ");

    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user["password"])) {
        respond(["error" => "Invalid credentials"]);
    }

    $token = createToken($user["id"], $secret, $jwt_algorithm, $jwt_issuer, $jwt_expiry);

    respond([
        "message" => "Login successful",
        "token" => $token
    ]);
}

/* =========================
   fallback
========================= */
respond(["error" => "Route not found"]);