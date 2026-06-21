<?php

/* =====================
   CACHE CONTROL LOGIC
===================== */

/**
 * Apply Cache-Control headers based on policy
 */
function applyCacheHeaders($policy = "no-store")
{
    switch ($policy) {

        case "no-store":
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            break;

        case "private":
            header("Cache-Control: private, max-age=60, must-revalidate");
            break;

        case "public":
            header("Cache-Control: public, max-age=300, s-maxage=300");
            break;

        case "short":
            header("Cache-Control: public, max-age=10");
            break;

        default:
            header("Cache-Control: no-store");
    }
}


/**
 * Add Vary header (important for CDN/browser caching correctness)
 */
function applyVary($headers = [])
{
    if (empty($headers)) return;

    header("Vary: " . implode(", ", $headers));
}


/**
 * Generate and send ETag
 */
function setETag($data)
{
    $etag = '"' . md5(json_encode($data, JSON_UNESCAPED_SLASHES)) . '"';
    header("ETag: $etag");
    return $etag;
}


/**
 * Check ETag and return 304 if matches
 */
function checkETag($etag)
{
    $clientETag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
    if (!$clientETag) {
        return false;
    }
    $clientETag = trim($clientETag);
    if ($clientETag === '') {
        return false;
    }
    return $clientETag === $etag;
}


/**
 * Set Last-Modified header
 */
function setLastModified($timestamp)
{
    $date = gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    header("Last-Modified: $date");
    return $timestamp;
}


/**
 * Check Last-Modified and return 304 if not modified
 */
function checkLastModified($timestamp)
{
    $header = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;
    if (!$header) return false;
    $clientTime = strtotime($header);
    if ($clientTime === false) return false;
    return (int)$clientTime >= (int)$timestamp;
}


/**
 * Combined helper (optional convenience function)
 */
function cacheValidate($etag = null, $lastModified = null)
{
    if ($etag) {
        checkETag($etag);
    }

    if ($lastModified) {
        checkLastModified($lastModified);
    }
}