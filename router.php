<?php
// Restrict the PHP development server to public application files.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (in_array($path, ['/assets/style.css', '/assets/app.js'], true)) return false;
if ($path === '/' || $path === '/index.php') { require __DIR__.'/index.php'; return true; }
http_response_code(404);
echo 'Not found';
