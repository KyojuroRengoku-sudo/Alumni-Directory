<?php
/* Logs the user out by destroying the session, both server-side and
   the session cookie itself. session_destroy() alone only clears the
   server-side data — the PHPSESSID cookie stays in the browser until
   it expires or is explicitly cleared. */

session_start();
session_unset();
session_destroy();

// Explicitly expire the session cookie in the browser.
// Use the options-array form (PHP 7.3+) so 'samesite' is always included —
// session_get_cookie_params() omits it on older PHP builds, meaning the
// positional-argument form would send the cookie without a SameSite attribute.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Strict',
    ]);
}

header('Location: ../pages/pb_login.php');
exit;
