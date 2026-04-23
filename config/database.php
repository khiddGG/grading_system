<?php
/**
 * Database Configuration
 * Uses PDO for secure MySQL connections
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'grading_system_v2');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="padding:2rem;font-family:sans-serif;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin:2rem;">
                  <h2 style="margin-top:0;">Database Connection Failed</h2>
                  <p>' . htmlspecialchars($e->getMessage()) . '</p>
                  <p><strong>Steps to fix:</strong></p>
                  <ol>
                    <li>Make sure <strong>Laragon MySQL</strong> is running.</li>
                    <li>Ensure you have imported <code>database/schema.sql</code>.</li>
                    <li>Check if the database <code>' . DB_NAME . '</code> exists in phpMyAdmin.</li>
                  </ol>
                 </div>');
        }
    }
    return $pdo;
}
