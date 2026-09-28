<?php
/**
 * Database Connection File using PHP PDO
 * Standard XAMPP Configuration
 */

$host = 'localhost';
$dbname = 'hostel_db';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // If database does not exist or connection failed
    $error_msg = $e->getMessage();
    // Provide a clear helper page if database is not yet imported
    die("
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #DEE2E6; border-radius: 8px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
        <h2 style='color: #000666; margin-top: 0;'>Hostel Management System - Database Setup</h2>
        <p style='color: #454652;'>Unable to connect to MySQL database <strong>hostel_db</strong>.</p>
        <div style='background: #ffdad6; color: #93000a; padding: 12px; border-radius: 4px; font-size: 13px; margin: 15px 0;'>
            <strong>Error:</strong> " . htmlspecialchars($error_msg) . "
        </div>
        <p style='font-size: 14px; color: #1a1c1c;'><strong>To fix this:</strong></p>
        <ol style='font-size: 13px; color: #454652; line-height: 1.6;'>
            <li>Open XAMPP Control Panel and start <strong>Apache</strong> and <strong>MySQL</strong>.</li>
            <li>Open <a href='http://localhost/phpmyadmin' target='_blank' style='color: #1a237e;'>phpMyAdmin</a>.</li>
            <li>Import the <code>database.sql</code> file located in the root of this project.</li>
            <li>Refresh this page.</li>
        </ol>
    </div>
    ");
}
