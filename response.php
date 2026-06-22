<?php

require_once "http_cache.php";

/* =====================
   MANAGE CACHE HEADERS
===================== */
function handleCacheHeaders($data, $options = [])
{
    applyCacheHeaders($options["cache"] ?? "no-store"); // defaulted to no-store to process all responses

    if (!empty($options["vary"])) {
        applyVary($options["vary"]);
    }

    $etag = null;
    $lastModified = null;

    if (($options["etag"] ?? false) && $data !== null) {
        $etag = generateETag($data);
        if (checkETag($etag)) {
            http_response_code(304);
            exit;
        }
    }

    if (!empty($options["last_modified"])) {
        $lastModified = is_numeric($options["last_modified"])
            ? $options["last_modified"]
            : strtotime($options["last_modified"]);
        if ($lastModified && checkLastModified($lastModified)) {
            http_response_code(304);
            exit;
        }
    }

    if ($etag) {
        setEtag($etag);
    }
    
    if ($lastModified) {
        setLastModified($lastModified);
    }
}

/* =====================
   SEND RESPONSE
===================== */
function respond($statusCode, $status, $message, $data = null, $options = [])
{
    handleCacheHeaders($data, $options);

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