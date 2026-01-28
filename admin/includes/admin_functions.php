<?php

/**
 * Robust HTTPS detection that works behind proxies/CDNs
 */
function isRequestHttps(): bool {
    $https = $_SERVER['HTTPS'] ?? '';
    $serverPort = (int)($_SERVER['SERVER_PORT'] ?? 0);
    $xfp = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
    $xForwardedSsl = strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '');
    // X-Forwarded-Proto may contain a comma-separated list
    $xfpContainsHttps = strpos($xfp, 'https') !== false;
    return (
        (is_string($https) && (strtolower($https) === 'on' || $https === '1')) ||
        $serverPort === 443 ||
        $xfpContainsHttps ||
        $xForwardedSsl === 'on'
    );
}

/**
 * Determine if the current request is on a local development host
 */
function isLocalhost(): bool {
	$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
	$hostnameOnly = explode(':', $host)[0]; // strip port
	return in_array($hostnameOnly, ['localhost', '127.0.0.1', '::1'], true);
}

/**
 * Get the admin base path dynamically
 * This function calculates the correct admin directory path
 */
function getAdminBasePath() {
    // Derive from the executing script's URL path for reliability under rewrites and subdirs
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php'), '/\\');
    if ($scriptDir === '' || $scriptDir === '.') {
        $scriptDir = '/';
    }
    // Ensure ends with a single slash and points to the admin directory
    $basePath = rtrim($scriptDir, '/') . '/';
    // If we are running inside a nested path (e.g., /subdir/admin), this still resolves correctly
    return $basePath;
}

/**
 * Generate admin URL for a given path
 * Similar to getBaseUrl() but specifically for admin paths
 */
function getAdminUrl($path = '') {
    $basePath = getAdminBasePath();
    
    // Handle empty path
    if (empty($path)) {
        return $basePath;
    }
    
    // Clean the path
    $path = ltrim($path, '/');
    
    // Combine basePath and path
    return $basePath . $path;
}

/**
 * Generate an absolute admin URL including scheme and host
 */
function getAdminAbsoluteUrl($path = '', $forceHttps = false) {
	$scheme = $forceHttps ? 'https' : (isRequestHttps() ? 'https' : 'http');
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$pathPart = getAdminUrl($path);
	return $scheme . '://' . $host . $pathPart;
}

/**
 * Check if current page matches given page ID
 */
function isActivePage($pageId, $activePage = null) {
    return isset($activePage) && $activePage === $pageId;
}

/**
 * Get the main site URL from admin
 * Goes up one directory from admin to reach the main site
 */
function getMainSiteUrl($path = '') {
    // Always point to site root (one level up from /admin/), absolute URL
    $scheme = isRequestHttps() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $clean = ltrim((string)$path, '/');
    $base = $scheme . '://' . $host . '/';
    return $base . $clean;
}