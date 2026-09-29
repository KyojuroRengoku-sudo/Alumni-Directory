<?php
/**
 * db.php
 *
 * Database connection. Edit the constants below to match your local
 * MySQL setup before running anything else.
 *
 * Anyone deploying this for real should pull these values from environment
 * variables instead of hardcoding them in a file that might get committed
 * to version control. For a school project, hardcoded is fine — just don't
 * carry this pattern into a real job.
 *
 * IMPORTANT: this file must never be directly accessible via URL.
 * Either place api/ outside the web root, or ensure your .htaccess denies
 * direct requests (see api/.htaccess in the project root).
 */

// Prevent direct HTTP access — this file should only ever be require()'d
// by another PHP script, never hit through the browser on its own.
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

define('DB_HOST', 'localhost');
define('DB_NAME', 's31200126_alumnidirectorydb');
define('DB_USER', 's31200126_alumnidirectorydb');
define('DB_PASS', 'Cauguiran2007'); // set your MySQL root/user password here

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}
