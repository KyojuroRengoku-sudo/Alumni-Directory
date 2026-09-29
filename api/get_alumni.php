<?php
/**
 * get_alumni.php
 *
 * Returns the alumni directory list as JSON.
 *
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

header('Content-Type: application/json');

// Must be logged in as alumni, staff, or student to access the directory.
if (
    empty($_SESSION['account_ID']) &&
    empty($_SESSION['staff_ID']) &&
    empty($_SESSION['student_mode'])
) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

require_once __DIR__ . '/db.php';

// Pagination params — defaults match the staff directory endpoint for consistency.
$perPage = max(1, min(100, (int) ($_GET['perPage'] ?? 20)));
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$baseWhere = "
    FROM account a
    LEFT JOIN staff st ON st.account_ID = a.account_ID
    LEFT JOIN graduation g ON g.account_ID = a.account_ID
    LEFT JOIN program p ON p.program_ID = g.program_ID
    LEFT JOIN college c ON c.college_ID = g.college_ID
    LEFT JOIN employment e ON e.account_ID = a.account_ID
    LEFT JOIN industry_sector s ON s.sector_ID = e.sector_ID
    WHERE st.account_ID IS NULL
      AND a.is_archived = 0
";

// Total row count for the caller to build its own pagination UI.
$countStmt = $pdo->query("SELECT COUNT(DISTINCT a.account_ID) " . $baseWhere);
$total     = (int) $countStmt->fetchColumn();

$sql = "
    SELECT
        a.account_ID,
        a.first_Name,
        a.last_Name,
        p.program_Name,
        g.graduation_Year,
        c.college_Name,
        e.occupation,
        e.employer,
        e.description AS employment_description,
        s.sector_Name,
        (a.photo IS NOT NULL) AS has_photo
    " . $baseWhere . "
    GROUP BY a.account_ID
    ORDER BY a.account_ID
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$result = array_map(function ($row) {
    $fullName = trim($row['first_Name'] . ' ' . $row['last_Name']);
    $initials = mb_substr($row['first_Name'], 0, 1) . mb_substr($row['last_Name'], 0, 1);

    return [
        'id'        => (int) $row['account_ID'],
        'initials'  => strtoupper($initials),
        'name'      => $fullName,
        'program'   => $row['program_Name'],
        'grad'      => $row['graduation_Year'],
        'college'   => $row['college_Name'],
        'role'      => $row['occupation'],
        'org'       => $row['employer'],
        'industry'  => $row['sector_Name'],
        'sector'    => $row['sector_Name'],
        'tagline'   => $row['occupation'] ? $row['occupation'] . ' · ' . $row['sector_Name'] : null,
        'image_url' => $row['has_photo'] ? "../api/get_image.php?id={$row['account_ID']}" : null,
    ];
}, $rows);

echo json_encode([
    'alumni'  => $result,
    'total'   => $total,
    'page'    => $page,
    'perPage' => $perPage,
]);
