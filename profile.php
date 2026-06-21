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
   ENFORCE AUTHENTICATION
========================= */
function requireAuth($authResult)
{
    if (isset($authResult["error"])) {
        respond(401, "error", $authResult["error"]);
    }

    return $authResult["user_id"];
}

/* =========================
   ENFORCE RATE LIMITING
========================= */
function handleRateLimit($rlResult)
{
    if (!$rlResult["allowed"]) {
        respond(429, "error", "Too many requests", [
         "count" => $rlResult["count"]
        ]);
    }
}

/* ======================================================
   GET /me
====================================================== */
if ($method === "GET" && $path === "/me") {

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer);
    $id = requireAuth($authResult);

    $key = $id . ":" . $method . ":" . $path;
    $rlResult = rateLimit($redis, $key, 60, 60); //(redis, key, limit, window_size)
    handleRateLimit($rlResult);

    $dbResult = $conn->query("
        SELECT id, name, email
        FROM users
        WHERE id=$id
    ");

    $user = $dbResult->fetch_assoc();

    if (!$user) {
        respond(404, "error", "User not found");
    }

    respond(200, "success", "User profile retrieved", [
        "user" => $user
    ], [
        "cache" => "private",
        "vary" => ["Authorization", "Accept-Encoding"],
        "etag" => true
    ]);
}

/* ======================================================
   GET /users (all or search by email)
====================================================== */
elseif ($method === "GET" && $path === "/users") {

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer); //for safety check only
    $id = requireAuth($authResult); 

    $key = $id . ":" . $method . ":" . $path;
    $rlResult = rateLimit($redis, $key, 30, 60); //(redis, key, limit, window_size)
    handleRateLimit($rlResult);

    /* search by email */
    if (isset($_GET["email"])) {

        $email = $_GET["email"];

        $dbResult = $conn->query("
            SELECT id, name, email
            FROM users
            WHERE email='$email'
            LIMIT 1
        ");

        $user = $dbResult->fetch_assoc();

        if (!$user) {
            respond(404, "error", "User not found");
        }

        respond(200, "success", "User profile retrieved", [
            "user" => $user
        ], [
            "cache" => "public",
            "vary" => ["Accept-Encoding"],
            "etag" => true
        ]);
    }

    /* all users */
    $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
    $limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 10;

    $page = max(1, $page);
    $limit = max(1, min(50, $limit));

    $offset = ($page - 1) * $limit;

    /* get total users */
    $totalResult = $conn->query("SELECT COUNT(*) as total FROM users");
    $totalRow = $totalResult->fetch_assoc();
    $total = (int)$totalRow["total"];

    $totalPages = max(1, ceil($total / $limit));

    /* backend check */
    if ($page > $totalPages) {
        respond(400, "error", "Page number exceeds available data");
    }

    $dbResult = $conn->query("
        SELECT id, name, email
        FROM users
        LIMIT $limit OFFSET $offset
    ");

    $users = [];
    while ($row = $dbResult->fetch_assoc()) {
        $users[] = $row;
    }

    respond(200, "success", "Users retrieved successfully", [
        "users" => $users,
        "pagination" => [
            "page" => $page,
            "limit" => $limit,
            "total" => $total,
            "total_pages" => $totalPages
        ]
    ], [
        "cache" => "public",
        "vary" => ["Accept-Encoding"],
        "etag" => true
    ]);
}

/* ======================================================
   PUT /me
====================================================== */
elseif ($method === "PUT" && $path === "/me") {

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer);
    $id = requireAuth($authResult);

    $key = $id . ":" . $method . ":" . $path;
    $rlResult = rateLimit($redis, $key, 20, 60); //(redis, key, limit, window_size)
    handleRateLimit($rlResult);

    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        respond(400, "error", "Missing request body");
        exit;
    }

    if (
        !isset($input["name"]) || trim($input["name"]) === "" ||
        !isset($input["email"]) || trim($input["email"]) === ""
    ) {
        respond(422, "error", "All fields are required");
    }

    $email = trim($input["email"]);
    
    /* check email format */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(422, "error", "Invalid email format");
    }

    $name = $input["name"];
    $email = $input["email"];

    /* =========================
       CHECK DUPLICATE EMAIL    
       (EXCLUDING CURRENT ONE)
    ========================= */
    $check = $conn->query("
        SELECT id FROM users WHERE email='$email' AND id!=$id LIMIT 1
    ");

    if ($check->num_rows > 0) {
        respond(409, "error", "Email already exists");
    }

    $conn->query("
        UPDATE users
        SET name='$name', email='$email'
        WHERE id=$id
    ");

    respond(200, "success", "Profile updated successfully");
}

/* ======================================================
   DELETE /me
====================================================== */
elseif ($method === "DELETE" && $path === "/me") {

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer); //for safety check only
    $id = requireAuth($authResult);

    $key = $id . ":" . $method . ":" . $path;
    $rlResult = rateLimit($redis, $key, 10, 60); //(redis, key, limit, window_size)
    handleRateLimit($rlResult);

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer);
    $id = requireAuth($authResult);

    $conn->query("DELETE FROM users WHERE id=$id");

    respond(200, "success", "Account deleted successfully");
}

/* ======================================================
   FALLBACK
====================================================== */
else {
    respond(404, "error", "Resource not found");
}