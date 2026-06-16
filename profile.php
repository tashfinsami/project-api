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
   ENFORCE AUTHENTICATION
========================= */
function requireAuth($authResult)
{
    if (isset($authResult["error"])) {
        http_response_code(401);

        respond([
            "status" => "error",
            "message" => $authResult["error"]
        ]);
    }

    return $authResult["user_id"];
}

/* ======================================================
   GET /me
====================================================== */
if ($method === "GET" && $path === "/me") {

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer);
    $id = requireAuth($authResult);

    $result = $conn->query("
        SELECT id, name, email
        FROM users
        WHERE id=$id
    ");

    respond($result->fetch_assoc());
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

        $result = $conn->query("
            SELECT id, name, email
            FROM users
            WHERE email='$email'
            LIMIT 1
        ");

        $user = $result->fetch_assoc();

        if (!$user) {
            http_response_code(404);

            respond([
                "status" => "error",
                "message" => "User not found"
            ]);
        }

        respond($user);
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
        http_response_code(400);

        respond([
            "status" => "error",
            "message" => "Page number exceeds available data"
        ]);
    }

    $result = $conn->query("
        SELECT id, name, email
        FROM users
        LIMIT $limit OFFSET $offset
    ");

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    respond($users);
}

/* ======================================================
   PUT /me
====================================================== */
elseif ($method === "PUT" && $path === "/me") {

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer);
    $id = requireAuth($authResult);

    $input = json_decode(file_get_contents("php://input"), true);

    $name = $input["name"];
    $email = $input["email"];

    $conn->query("
        UPDATE users
        SET name='$name', email='$email'
        WHERE id=$id
    ");

    http_response_code(200);

    respond([
        "status" => "success",
        "message" => "Profile updated successfully"
    ]);
}

/* ======================================================
   DELETE /me
====================================================== */
elseif ($method === "DELETE" && $path === "/me") {

    $authResult = authenticateUser($secret, $jwt_algorithm, $jwt_issuer);
    $id = requireAuth($authResult);

    $conn->query("DELETE FROM users WHERE id=$id");

    http_response_code(200);

    respond([
        "status" => "success",
        "message" => "Account deleted successfully"
    ]);
}

/* ======================================================
   FALLBACK
====================================================== */
else {
    http_response_code(404);

    respond([
        "status" => "error",
        "message" => "Resource not found"
    ]);
}