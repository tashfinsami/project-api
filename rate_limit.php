<?php

function rateLimit($redis, $key, $limit = 60, $window = 60)
{
    $redisKey = "rate:" . md5($key);

    // increment request count
    $count = $redis->incr($redisKey); //atomic increment

    // set expiry only on first request
    if ($count == 1) {
        $redis->expire($redisKey, $window); //key deleted automatically after time expires
    }

    // check limit
    if ($count > $limit) {
        return [
            "allowed" => false,
            "retry_after" => $redis->ttl($redisKey) //time to live
        ];
    }

    return [
        "allowed" => true,
        "count" => $count
    ];
}