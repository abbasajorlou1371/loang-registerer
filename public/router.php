<?php

declare(strict_types=1);

// Front controller: php -S localhost:8000 -t public public/router.php

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = urldecode($rawPath);
$file = __DIR__ . $uri;

if (
    $uri !== '/'
    && $uri !== '/index.php'
    && $uri !== '/router.php'
    && is_file($file)
) {
    return false;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/calculator.php';
require_once APP_ROOT . '/includes/eligibility.php';
require_once APP_ROOT . '/includes/application.php';
require_once APP_ROOT . '/includes/payment.php';
require_once APP_ROOT . '/includes/transaction_message.php';

$socketPublicUrl = getenv('SOCKET_PUBLIC_URL') ?: 'http://127.0.0.1:3001';
$socketConnectSrc = $socketPublicUrl
    . ' '
    . preg_replace('#^http://#i', 'ws://', $socketPublicUrl)
    . ' '
    . preg_replace('#^https://#i', 'wss://', $socketPublicUrl);

header(
    "Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' {$socketPublicUrl}; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
    . "font-src 'self' https://fonts.gstatic.com; "
    . "img-src 'self' data:; "
    . "connect-src 'self' {$socketConnectSrc}; "
    . "frame-ancestors 'none'; "
    . "base-uri 'self'; "
    . "form-action 'self'"
);

$uri = rtrim($uri, '/') ?: '/';
if ($uri === '/index.php') {
    $uri = '/';
}

$_SERVER['ROUTE_PATH'] = $uri;

$loanSlugs = ['mehrabani-melli', 'etebar-melli', 'ezdevaj', 'farzand-avi'];

$routes = [
    '/' => 'home.php',
    '/loans' => 'loans/index.php',
    '/compare' => 'compare.php',
    '/calculator' => 'calculator/index.php',
    '/eligibility' => 'eligibility/index.php',
    '/apply' => 'apply/index.php',
    '/apply/review' => 'apply/review.php',
    '/apply/confirm' => 'apply/confirm.php',
    '/pay' => 'pay/index.php',
    '/pay/captcha' => 'pay/captcha.php',
    '/pay/success' => 'pay/success.php',
    '/pay/failed' => 'pay/failed.php',
    '/track' => 'track/index.php',
    '/faq' => 'faq.php',
    '/admin' => 'admin/dashboard.php',
    '/admin/login' => 'admin/login.php',
    '/admin/logout' => 'admin/logout.php',
    '/admin/requests' => 'admin/requests.php',
    '/admin/disposable-codes' => 'admin/disposable-codes.php',
    '/api/transaction-message' => 'api/transaction-message.php',
];

try {
    if (preg_match('#^/loans/([a-z0-9-]+)$#', $uri, $matches) === 1) {
        if (in_array($matches[1], $loanSlugs, true)) {
            $_GET['slug'] = $matches[1];
            require APP_ROOT . '/pages/loans/detail.php';
            return;
        }
        http_response_code(404);
        require APP_ROOT . '/pages/errors/404.php';
        return;
    }

    if (preg_match('#^/admin/requests/([0-9]+)$#', $uri, $matches) === 1) {
        $_GET['id'] = $matches[1];
        require APP_ROOT . '/pages/admin/request-detail.php';
        return;
    }

    if (isset($routes[$uri])) {
        require APP_ROOT . '/pages/' . $routes[$uri];
        return;
    }

    http_response_code(404);
    require APP_ROOT . '/pages/errors/404.php';
} catch (Throwable $e) {
    app_log('error', 'Unhandled application error', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    http_response_code(500);
    require APP_ROOT . '/pages/errors/500.php';
}
