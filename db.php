<?php
// Database connection only (tables already created in MySQL)
$host = 'mysql.hostinger.com';  // or ip like 'mysql.hostinger.com'
$dbname = 'u585809268_cheese_db';  // usually username_dbname format
$username = 'u585809268_root';    // your MySQL username
$password = 'Queseria2025.1';


$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);