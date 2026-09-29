<?php
/**
 * student_logout.php
 *
 * Destroys the student-mode session and sends the visitor back to the
 * public landing page. Mirrors logout.php but targets pb_landing.html
 * instead of pb_login.html.
 *
 * Place this file alongside logout.php in your /api/ directory.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
session_unset();
session_destroy();

// Explicitly expire the session cookie in the browser — session_destroy() only
// clears the server-side data; the PHPSESSID cookie stays until it is overwritten.
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: ../pages/pb_landing.html');
exit;
