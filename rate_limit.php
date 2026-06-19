<?php

/* =========================
   RATE LIMITER 
   (SLIDING WINDOW LOG)
========================= */
function rateLimit($redis, $key, $limit = 60, $window = 60)
{
    $redisKey = "rl:" . md5($key);

    $now = microtime(true); //count in millisecs

    // remove old requests outside the window (on sorted list)
    $redis->zRemRangeByScore($redisKey, 0, $now - $window); // (key, min_score, max_score)

    // count requests in current window
    $count = $redis->zCard($redisKey);

    // if limit exceeded
    if ($count >= $limit) {
        // get oldest request timestamp for retry info
        $oldest = $redis->zRange($redisKey, 0, 0, true); // (key, index, total_vals,  return score or not)

        $oldestTime = $oldest ? array_values($oldest)[0] : $now; // assign oldest timestamp or current timestamp(first req)

        return [
            "allowed" => false,
            "retry_after" => ceil(($oldestTime + $window) - $now)
        ];
    }

    // add current request
    $redis->zAdd($redisKey, $now, uniqid("", true)); // (key, timestamp, unique req id)

    // cleanup (to save memory)
    $redis->expire($redisKey, $window); // delete key as not needed after (last timestamp + window) secs

    return [
        "allowed" => true,
        "count" => $count + 1
    ];
}