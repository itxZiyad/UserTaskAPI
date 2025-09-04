<?php
$host = getenv('MYSQL_HOST') ?: '127.0.0.1';
$port = getenv('MYSQL_PORT') ?: '3307';
$user = getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQL_PASSWORD') ?: '';
$dbName = getenv('MYSQL_DB') ?: 'user_task_api';

try {
	$pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);
	$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
	echo "Database '{$dbName}' is ready\n";
} catch (Throwable $e) {
	echo "Error: " . $e->getMessage() . "\n";
	exit(1);
}
