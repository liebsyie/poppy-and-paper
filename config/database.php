<?php
// Local development database connection.
// Update these values if your local MySQL configuration differs.
$host = 'localhost';
$dbname = 'poppy_paper_db';
$username = 'root';
$password = '';

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $exception) {
    // Log detailed errors privately; show a generic message to visitors.
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Database connection failed. Please check the local database configuration.');
}
