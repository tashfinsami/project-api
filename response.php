<?php

function respond($statusCode, $status, $message, $data = null)
{
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