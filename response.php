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

    if (($options["etag"] ?? false) && $data !== null) {

        $etag = generateETag($data);
    
        if (checkETag($etag)) {
            http_response_code(304);
            applyVary(["Accept-Encoding"]);
            setEtag($etag);
            exit;
        }
    
        setEtag($etag); // only for 200 response
    }

    if (!empty($options["last_modified"])) {
        $lastModified = is_numeric($options["last_modified"])
            ? $options["last_modified"]
            : strtotime($options["last_modified"]);

        if ($lastModified && checkLastModified($lastModified)) {
            http_response_code(304);
            applyVary(["Accept-Encoding"]);
            setLastModified($lastModified);
            exit;
        }

        if ($lastModified) {
            setLastModified($lastModified); // only for 200 response
        }
    }
}

/* =====================
   ENABLE COMPRESSION
===================== */
function enableCompression()
{
    if (headers_sent()) return;

    $encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';

    if (strpos($encoding, 'br') !== false && function_exists('brotli_compress')) {
        header("Content-Encoding: br");
        ob_start("brotli_compress");
    }
    elseif (strpos($encoding, 'gzip') !== false) {
        header("Content-Encoding: gzip");
        ob_start("ob_gzhandler");
    }
    else {
        ob_start();
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

    header("Vary: Accept-Encoding");

    $response = [
        "status" => $status,
        "message" => $message
    ];

    if ($data !== null) {
        $response["data"] = $data;
    }

    $json = json_encode($response);

    enableCompression(); // all responses go through response function

    echo $json;

    exit;
}