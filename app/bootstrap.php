<?php

declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Configuration missing. Copy config/config.example.php to config/config.php and configure the database.');
}
$config = require $configPath;
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('kahloon_crm');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => ($config['app']['base_path'] ?? '') ?: '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function db(): PDO
{
    static $pdo;
    global $config;
    if (!$pdo) {
        $db = $config['db'];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'] ?? 3306, $db['name']);
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(string $path = ''): string
{
    global $config;
    $base = rtrim((string)($config['app']['base_path'] ?? ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . base_path($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('The form expired. Go back, reload the page, and try again.');
    }
}

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        redirect('/login');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function audit(string $entity, int $id, string $action, ?array $old = null, ?array $new = null): void
{
    $stmt = db()->prepare('INSERT INTO audit_logs (user_id, entity_type, entity_id, action, old_values, new_values, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        current_user()['id'] ?? null,
        $entity,
        $id,
        $action,
        $old === null ? null : json_encode($old, JSON_THROW_ON_ERROR),
        $new === null ? null : json_encode($new, JSON_THROW_ON_ERROR),
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
