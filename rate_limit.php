<?php

function rateLimit($key, $limit = 60, $window = 60)
{
    $file = sys_get_temp_dir() . "/rate_" . md5($key);

    $now = time();

    $data = [
        "count" => 0,
        "start" => $now
    ];

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);

        // reset window
        if (($now - $data["start"]) >= $window) {
            $data = [
                "count" => 0,
                "start" => $now
            ];
        }
    }

    $data["count"]++;

    file_put_contents($file, json_encode($data));

    if ($data["count"] > $limit) {
        return [
            "allowed" => false,
            "retry_after" => $window - ($now - $data["start"])
        ];
    }

    return [
        "allowed" => true
    ];
}