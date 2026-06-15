<?php

require_once "vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/* =====================
   CREATE TOKEN
===================== */
function createToken($userId, $secret, $algorithm, $issuer, $expirySeconds)
{
    $payload = [
        "iss" => $issuer,
        "iat" => time(),
        "exp" => time() + $expirySeconds,
        "user_id" => $userId
    ];

    return JWT::encode($payload, $secret, $algorithm);
}

/* =========================
   GET TOKEN and VERIFY
========================= */
function getUserId($secret, $algorithm, $issuer)
{
    $headers = getallheaders();
    $auth = $headers["Authorization"] ?? "";

    if (!$auth) {
        return ["error" => "Token missing"];
    }

    $token = str_replace("Bearer ", "", $auth);

    try {
        $decoded = JWT::decode($token, new Key($secret, $algorithm));

        if (!isset($decoded->iss) || $decoded->iss !== $issuer) {
            return ["error" => "Invalid token issuer"];
        }

        return ["user_id" => $decoded->user_id];

    } catch (Exception $e) {
        return ["error" => "Invalid or expired token"];
    }
}