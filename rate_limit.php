<?php

/* =========================
   RATE LIMITER 
   (SLIDING WINDOW COUNTER)
========================= */
function rateLimit($redis, $key, $limit = 60, $window = 60)
{
    $now = microtime(true); //count in microsecs

    // window calculation
    $currentWindow = floor($now / $window) * $window;
    $previousWindow = $currentWindow - $window;

    $hash = md5($key);

    $currentKey = "rl:$hash:$currentWindow";
    $previousKey = "rl:$hash:$previousWindow";

    // lua script (for atomic execution)
    $lua = <<<LUA
-- KEYS[1] = current key
-- KEYS[2] = previous key
-- ARGV[1] = limit
-- ARGV[2] = window
-- ARGV[3] = now

local currentKey = KEYS[1]
local previousKey = KEYS[2]

local limit = tonumber(ARGV[1])
local window = tonumber(ARGV[2])
local now = tonumber(ARGV[3])

-- increment current window
local currentCount = redis.call("INCR", currentKey)

-- expire current key (for memory cleanup)
redis.call("EXPIRE", currentKey, window * 2)

-- get previous window count
local previousCount = tonumber(redis.call("GET", previousKey) or "0")

-- sliding calculation
local currentWindowStart = math.floor(now / window) * window
local elapsed = now - currentWindowStart

local weight = (window - elapsed) / window

local count = currentCount + (previousCount * weight)
count = math.floor(count + 0.0000001)

-- block or allow
if count > limit then
    return {0, count}
end

return {1, count}
LUA;

    // execute in redis
    $result = $redis->eval(
        $lua,
        [$currentKey, $previousKey, $limit, $window, $now],
        2
    );

    return [
        "allowed" => $result[0] == 1,
        "count" => $result[1]
    ];
}