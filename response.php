<?php

/* =====================
   CACHE CONTROL LOGIC
===================== */
function applyCacheHeaders($cache)
{
    switch ($cache) {

        case "no-store":
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache"); // for http/1.0 compatibility
            header("Expires: 0");
            break;

        case "private":
            header("Cache-Control: private, max-age=60, must-revalidate");
            header("Vary: Authorization, Accept-Encoding"); // for different user tokens and gzip/non gzip
            break;

        case "public":
            header("Cache-Control: public, max-age=300, s-maxage=300");
            header("Vary: Accept-Encoding"); // for gzip/non gzip
            break;

        default:
            header("Cache-Control: no-store");
    }
}

function respond($statusCode, $status, $message, $data = null, $cache = "no-store")
{
    http_response_code($statusCode);

    header("Content-Type: application/json; charset=utf-8");

    applyCacheHeaders($cache); // default no-store to handle all responses

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