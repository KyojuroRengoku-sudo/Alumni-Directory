<?php
/**
 * Handles login form submission from pb_login.html.
 *
 * The "email" field now accepts EITHER a real email address OR a staff
 * ID (e.g. "00-1001") — same single field, same single Sign In button,
 * for everyone. After the password checks out, we look at whether that
 * account also has a row in `staff`:
 *   - yes -> redirect to pv_staff.php (staff dashboard)
 *   - no  -> redirect to pv_main.php (regular alumni page, unchanged)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,      // HTTPS only
        'httponly' => true,      // JS cannot read the cookie
        'samesite' => 'Strict',  // blocks CSRF via cross-site requests
    ]);
    session_start();
}

// CSRF Protection Check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/pb_login.php'); // Updated extension
    exit;
}

$identifier = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    header('Location: ../pages/pb_login.php?error=missing');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT a.account_ID, a.first_Name, a.last_Name, a.school_ID, a.password,
            s.staff_ID, s.staff_level
     FROM account a
     LEFT JOIN staff s ON s.account_ID = a.account_ID
     WHERE a.email = ? OR a.school_ID = ?'
);
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: ../pages/pb_login.php?error=invalid');
    exit;
}

// Regenerate session ID on every successful login (privilege boundary)
session_regenerate_id(true);

if (!empty($user['staff_ID'])) {
    // This account is staff — send to the staff dashboard instead.
    $_SESSION['staff_ID']        = (int) $user['staff_ID'];
    $_SESSION['staff_level']     = (int) $user['staff_level'];
    $_SESSION['account_ID']      = (int) $user['account_ID'];
    $_SESSION['staff_name']      = trim($user['first_Name'] . ' ' . $user['last_Name']);
    $_SESSION['staff_school_ID'] = $user['school_ID'];
    header('Location: ../pages/pv_staff.php');
    exit;
}

// Login successful — regular alumni
$_SESSION['account_ID'] = (int) $user['account_ID'];
header('Location: ../pages/pv_main.php');
exit;
