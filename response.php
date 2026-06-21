<?php

require_once "http_cache.php";

function respond($statusCode, $status, $message, $data = null, $options = [])
{
    applyCacheHeaders($options["cache"] ?? "no-store"); // defaulted to no-store to process all responses

    if (!empty($options["vary"])) {
        applyVary($options["vary"]);
    }

    if (($options["etag"] ?? false) && $data !== null) {
        $etag = setETag($data);
        if (checkETag($etag)) {
            http_response_code(304);
            exit;
        }
    }

    http_response_code($statusCode);

    header("Content-Type: application/json; charset=utf-8");

    $response = [
        "status" => $status,
        "message" => $message
    ];

    if ($data !== null) {
        $response["data"] = $data;
    }

    echo json_encode($response);

    exit;
}