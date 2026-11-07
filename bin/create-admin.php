<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line.\n");
    exit(1);
}

$name = trim((string)readline('Administrator name: '));
$email = strtolower(trim((string)readline('Administrator email: ')));
$password = (string)readline('Administrator password (12+ characters): ');
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Provide a name, valid email, and password of at least 12 characters.\n");
    exit(1);
}
$role = db()->prepare("SELECT id FROM roles WHERE name = 'Super Admin' LIMIT 1");
$role->execute();
$roleId = $role->fetchColumn();
if (!$roleId) {
    fwrite(STDERR, "Roles are missing. Import database/schema.sql first.\n");
    exit(1);
}
try {
    $stmt = db()->prepare("INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)");
    $stmt->execute([$roleId, $name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Administrator created. Sign in at the CRM login page.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, "Could not create administrator. The email may already exist.\n");
    exit(1);
}
