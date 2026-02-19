<?php
// ============================================================
//  api/admin_students.php  —  GET  List all students + results
//  Requires admin or holder token
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonErr('Method not allowed', 405);

$admin = requireAuth();
if (!in_array($admin['role'], ['admin', 'holder'])) {
    jsonErr('Forbidden', 403);
}

$db = getDB();

// ── Fetch all students ────────────────────────────────────────
$students = $db->query(
    'SELECT id, name, email, roll_no, branch, cgpa, phone, created_at
     FROM users WHERE role = "student" ORDER BY created_at DESC'
)->fetchAll();

// ── Attach scores and completed rounds ────────────────────────
foreach ($students as &$student) {
    $uid = $student['id'];

    // Latest score per company+round
    $scStmt = $db->prepare(
        'SELECT company, round, score, passed, taken_at
         FROM exam_results
         WHERE user_id = :uid
         ORDER BY taken_at DESC'
    );
    $scStmt->execute([':uid' => $uid]);
    $scores = [];
    foreach ($scStmt->fetchAll() as $row) {
        $key = $row['company'] . '_' . $row['round'];
        if (!isset($scores[$key])) {
            $scores[$key] = [
                'score'    => (int)$row['score'],
                'passed'   => (bool)$row['passed'],
                'taken_at' => $row['taken_at'],
            ];
        }
    }
    $student['scores'] = $scores;

    // Completed round list
    $crStmt = $db->prepare('SELECT company, round FROM completed_rounds WHERE user_id = :uid');
    $crStmt->execute([':uid' => $uid]);
    $cr = [];
    foreach ($crStmt->fetchAll() as $row) {
        $cr[$row['company']][] = $row['round'];
    }
    $student['completedRounds'] = $cr;
    $student['id'] = (int)$student['id'];
    if ($student['cgpa']) $student['cgpa'] = (float)$student['cgpa'];
}

jsonOk(['students' => $students]);
