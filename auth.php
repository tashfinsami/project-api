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

        http_response_code(409);

        respond([
            "status" => "error",
            "message" => "Email already exists"
        ]);
    }

    /* =========================
       INSERT USER
    ========================= */
    $conn->query("
        INSERT INTO users(name,email,password)
        VALUES('$name','$email','$password')
    ");

    http_response_code(201);

    respond([
        "status" => "success",
        "message" => "User created successfully"
    ]);
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
    $result = $conn->query("
        SELECT * FROM users WHERE email='$email'
    ");

    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user["password"])) {

        http_response_code(401);

        respond([
            "status" => "error",
            "message" => "Invalid credentials"
        ]);
    }
    
    /* =========================
       ISSUE JWT
    ========================= */
    $token = createToken($user["id"], $secret, $jwt_algorithm, $jwt_issuer, $jwt_expiry);

    http_response_code(200);

    respond([
        "status" => "success",
        "message" => "Login successful",
        "token" => $token
    ]);
}

/* =========================
   FALLBACK
========================= */

http_response_code(404);

respond([
    "status" => "error",
    "message" => "Resource not found"
]);