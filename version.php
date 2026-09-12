<?php
/**
 * BEAUTY PAGEANT JUDGING AND TABULATION SYSTEM
 * Application Versioning & Cache Busting Utility
 */

define('APP_VERSION', '1.0.5');

/**
 * Returns cache-busted URL with filemtime timestamp
 */
function asset_url($path) {
    $fullPath = __DIR__ . '/' . ltrim($path, '/');
    if (file_exists($fullPath)) {
        $mtime = filemtime($fullPath);
        return $path . '?v=' . $mtime;
    }
    return $path . '?v=' . APP_VERSION;
}
