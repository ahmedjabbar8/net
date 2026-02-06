<?php
// HealthPro Ultra-Fast Router for PHP Built-in Server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

$file = __DIR__ . $uri;

// 1. If it's a physical file (CSS, JS, Images), serve it directly
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// 2. Handle Clean URLs (e.g., /dashboard -> dashboard.php)
$phpFile = __DIR__ . $uri . '.php';
if (file_exists($phpFile)) {
    include $phpFile;
    exit;
}

// 3. Handle Directories (e.g., / -> index.php)
if (is_dir($file)) {
    $indexFile = rtrim($file, '/') . '/index.php';
    if (file_exists($indexFile)) {
        include $indexFile;
        exit;
    }
}

// 4. Default to index.php if nothing found (or show 404)
if (file_exists(__DIR__ . '/index.php')) {
    include __DIR__ . '/index.php';
    exit;
}

return false;
?>