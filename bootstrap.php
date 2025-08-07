<?php

/**
 * Bootstrap file for Router integration
 * 
 * This file can be included in your existing PHP files to add router functionality
 * while maintaining compatibility with your current .htaccess setup.
 */

// Include functions to get base path
require_once __DIR__ . '/includes/functions.php';

// Get dynamic base path
function getBootstrapBasePath() {
    // Get the document root and current script directory
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    $currentDir = __DIR__;
    
    // Calculate the relative path from document root
    $relativePath = str_replace($documentRoot, '', $currentDir);
    
    // Ensure it starts with / and normalize slashes
    $basePath = '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
    
    // Remove trailing slash if not root
    if ($basePath !== '/') {
        $basePath = rtrim($basePath, '/');
    }
    
    return $basePath;
}

// Only run router for specific patterns to avoid conflicts with existing pages
function shouldUseRouter() {
    $uri = $_SERVER['REQUEST_URI'];
    
    // Remove query string
    if (($pos = strpos($uri, '?')) !== false) {
        $uri = substr($uri, 0, $pos);
    }
    
    // Remove base path
    $basePath = getBootstrapBasePath();
    if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }
    
    // Ensure URI starts with /
    if (empty($uri) || $uri[0] !== '/') {
        $uri = '/' . $uri;
    }
    
    $uri = rtrim($uri, '/') ?: '/';
    
    // Routes that should use the router
    $routerPaths = [
        '/api/',          // All API routes
        '/admin/',        // Admin routes
        '/blog',          // Blog routes
        '/user/',         // User profile routes
        '/special-offers', // Special offers
        '/ajax/',         // AJAX endpoints
        '/app/'           // SPA routes
    ];
    
    foreach ($routerPaths as $path) {
        if (strpos($uri, $path) === 0) {
            return true;
        }
    }
    
    return false;
}

// Initialize router if needed
if (shouldUseRouter()) {
    require_once __DIR__ . '/routes.php';
    // The router will handle the request and exit if matched
    // If we reach here, it means no route was matched
}

// Helper function to include the router in existing files
function initRouter() {
    if (shouldUseRouter()) {
        require_once __DIR__ . '/routes.php';
    }
} 