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
   ENFORCE AUTHENTICATION
========================= */
function requireAuth($authResult)
{
    if (isset($authResult["error"])) {
        respond(401, "error", $authResult["error"]);
    }

    return $authResult["user_id"];
}

/* ======================================================
   GET /me
====================================================== */
if ($method === "GET" && $path === "/me") {

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer);
    $id = requireAuth($authResult);

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
    ]);
}

/* ======================================================
   GET /users (all or search by email)
====================================================== */
elseif ($method === "GET" && $path === "/users") {

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer); //for safety check only
    $id = requireAuth($authResult); //only for safety checking

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
    ]);
}

/* ======================================================
   PUT /me
====================================================== */
elseif ($method === "PUT" && $path === "/me") {

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer);
    $id = requireAuth($authResult);

    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input["name"]) || !isset($input["email"])) {
        respond(422, "error", "Name and email are required");
    }

    $name = $input["name"];
    $email = $input["email"];

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