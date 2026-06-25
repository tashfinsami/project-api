<?php

require_once "vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/* =====================
   GENERATE TOKEN
===================== */
function generateToken($payload, $secret, $algorithm)
{
    $defaultExpirySeconds = 900; //default ttl

    $payload["iat"] = $payload["iat"] ?? time();
    $payload["exp"] = $payload["exp"] ?? $payload["iat"] + $defaultExpirySeconds;

    return JWT::encode($payload, $secret, $algorithm);
}

/* =========================
   GET BEARER TOKEN
========================= */
function getBearerToken()
{
    $headers = getallheaders();
    $auth = $headers["Authorization"] ?? "";

    if (!$auth) {
        return ["error" => "Authentication token is missing"];
    }

    if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
        return ["error" => "Authentication token is missing"];
    }

    return ["token" => $matches[1]];
}

/* =========================
   VERIFY TOKEN
========================= */
function verifyToken($token, $secret, $algorithm, $issuer)
{
    try {
        $decoded = JWT::decode($token, new Key($secret, $algorithm));

        if (!isset($decoded->iss) || $decoded->iss !== $issuer) {
            return ["error" => "Invalid token issuer"];
        }

        return ["decoded" => $decoded];

    } catch (Exception $e) {
        return ["error" => "Authentication token is invalid or expired"];
    }
}