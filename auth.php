<?php

require_once "config.php";
require_once "jwt.php";
require_once "response.php";
require_once "rate_limit.php";

/* =========================
   GET PATH
========================= */
$method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$script = $_SERVER["SCRIPT_NAME"];
$path = str_replace($script, "", $uri);

/* =========================
   PAYLOAD SIZE LIMIT
========================= */
if (in_array($method, ["POST", "PUT", "PATCH"])) {
   $maxPayloadSize = 20 * 1024; //20 KB limit
   if (
       isset($_SERVER["CONTENT_LENGTH"]) &&
       $_SERVER["CONTENT_LENGTH"] > $maxPayloadSize
   ) {
       respond(413, "error", "Payload too large");
   }
}

/* =========================
   ENFORCE RATE LIMITING
========================= */
function handleRateLimit($rlResult)
{
    if (!$rlResult["allowed"]) {
        respond(429, "error", "Too many requests");
    }
}

/* =========================
   BODY
========================= */
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
   respond(400, "error", "Missing request body");
   exit;
}

/* =========================
   SIGNUP
========================= */
if ($path === "/signup" && $method === "POST") {

    $key = $_SERVER["REMOTE_ADDR"] . ":" . $method . ":" . $path;
    $rlResult = rateLimit($key, 5, 60); //(key, limit, window_size)
    handleRateLimit($rlResult);

    if (
       !isset($data["name"]) || trim($data["name"]) === "" ||
       !isset($data["email"]) || trim($data["email"]) === "" ||
       !isset($data["password"]) || trim($data["password"]) === ""
    ) {
       respond(422, "error", "All fields are required");
    }

    $email = trim($data["email"]);

    /* check email format */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
       respond(422, "error", "Invalid email format");
    }

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

    $key = $_SERVER["REMOTE_ADDR"] . ":" . $method . ":" . $path;
    $rlResult = rateLimit($key, 5, 60); //(key, limit, window_size)
    handleRateLimit($rlResult);

    $key = $_SERVER["REMOTE_ADDR"] . ":" . $method . ":" . $path;

    if (
       !isset($data["email"]) || trim($data["email"]) === "" ||
       !isset($data["password"]) || trim($data["password"]) === ""
    ) {
       respond(422, "error", "All fields are required");
    }

    $email = trim($data["email"]);

    /* check email format */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
       respond(422, "error", "Invalid email format");
    }

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