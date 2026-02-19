<?php
// ============================================================
//  api/login.php  —  POST  Login for all roles
// ============================================================
//  Expected JSON body:
//  {
//    "email"   : "arjun@student.com",
//    "password": "mypassword",
//    "portal"  : "student"         // "student" | "admin" | "holder"
//  }
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErr('Method not allowed', 405);
}

$body   = getBody();
$email  = trim($body['email']    ?? '');
$pass   = trim($body['password'] ?? '');
$portal = trim($body['portal']   ?? 'student');

if (!$email || !$pass) jsonErr('Email and password are required');

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

// ── Wrong email ───────────────────────────────────────────────
if (!$user) jsonErr('Invalid email or password');

// ── Wrong password ────────────────────────────────────────────
if (!password_verify($pass, $user['password_hash'])) {
    jsonErr('Invalid email or password');
}

// ── Portal mismatch ───────────────────────────────────────────
if ($portal === 'student' && $user['role'] !== 'student') {
    jsonErr('This account does not belong to the Student Portal. Please use the correct portal.');
}
if ($portal === 'admin' && $user['role'] !== 'admin') {
    jsonErr('This account does not belong to the Admin Portal. Please use the correct portal.');
}
if ($portal === 'holder' && $user['role'] !== 'holder') {
    jsonErr('This account does not belong to the Website Holder Portal. Please use the correct portal.');
}

// ── Invalidate old sessions (optional: keep only latest) ──────
$db->prepare('DELETE FROM sessions WHERE user_id = :uid')->execute([':uid' => $user['id']]);

// ── Create new session ────────────────────────────────────────
$token = generateToken();
$db->prepare(
    'INSERT INTO sessions (user_id, token, expires_at) VALUES (:uid, :token, :exp)'
)->execute([':uid' => $user['id'], ':token' => $token, ':exp' => sessionExpiry()]);

// ── Fetch completed rounds and scores for this user ───────────
$crStmt = $db->prepare(
    'SELECT company, round FROM completed_rounds WHERE user_id = :uid'
);
$crStmt->execute([':uid' => $user['id']]);
$completedRounds = [];
foreach ($crStmt->fetchAll() as $row) {
    $completedRounds[$row['company']][] = $row['round'];
}

$scStmt = $db->prepare(
    'SELECT company, round, score FROM exam_results
     WHERE user_id = :uid
     ORDER BY taken_at DESC'
);
$scStmt->execute([':uid' => $user['id']]);
$scores = [];
foreach ($scStmt->fetchAll() as $row) {
    $key = $row['company'] . '_' . $row['round'];
    if (!isset($scores[$key])) {            // keep latest only
        $scores[$key] = (int)$row['score'];
    }
}

jsonOk([
    'token' => $token,
    'user'  => [
        'id'               => (int)$user['id'],
        'name'             => $user['name'],
        'email'            => $user['email'],
        'role'             => $user['role'],
        'roll_no'          => $user['roll_no'],
        'branch'           => $user['branch'],
        'cgpa'             => $user['cgpa'] ? (float)$user['cgpa'] : null,
        'phone'            => $user['phone'],
        'dept'             => $user['dept'],
        'college'          => $user['college'],
        'completedRounds'  => $completedRounds,
        'scores'           => $scores,
    ],
]);
