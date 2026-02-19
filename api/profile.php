<?php
// ============================================================
//  api/profile.php  —  GET (fetch) / POST (update) profile
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

$user = requireAuth();    // any logged-in user
$db   = getDB();

// ── GET: return current profile ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Enrich with exam data for students
    $completedRounds = [];
    $scores          = [];

    if ($user['role'] === 'student') {
        $crStmt = $db->prepare('SELECT company, round FROM completed_rounds WHERE user_id = :uid');
        $crStmt->execute([':uid' => $user['id']]);
        foreach ($crStmt->fetchAll() as $row) {
            $completedRounds[$row['company']][] = $row['round'];
        }

        $scStmt = $db->prepare(
            'SELECT company, round, score FROM exam_results
             WHERE user_id = :uid ORDER BY taken_at DESC'
        );
        $scStmt->execute([':uid' => $user['id']]);
        foreach ($scStmt->fetchAll() as $row) {
            $key = $row['company'] . '_' . $row['round'];
            if (!isset($scores[$key])) {
                $scores[$key] = (int)$row['score'];
            }
        }
    }

    jsonOk([
        'user' => [
            'id'              => (int)$user['id'],
            'name'            => $user['name'],
            'email'           => $user['email'],
            'role'            => $user['role'],
            'roll_no'         => $user['roll_no'],
            'branch'          => $user['branch'],
            'cgpa'            => $user['cgpa'] ? (float)$user['cgpa'] : null,
            'phone'           => $user['phone'],
            'dept'            => $user['dept'],
            'college'         => $user['college'],
            'completedRounds' => $completedRounds,
            'scores'          => $scores,
        ],
    ]);
}

// ── POST: update profile fields ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getBody();

    $name   = trim($body['name']   ?? $user['name']);
    $branch = trim($body['branch'] ?? $user['branch'] ?? '');
    $cgpa   = isset($body['cgpa'])  ? (float)$body['cgpa']  : null;
    $phone  = trim($body['phone']  ?? $user['phone'] ?? '');

    if (!$name) jsonErr('Name cannot be empty');

    $db->prepare(
        'UPDATE users SET name=:name, branch=:branch, cgpa=:cgpa, phone=:phone WHERE id=:id'
    )->execute([
        ':name'   => $name,
        ':branch' => $branch,
        ':cgpa'   => $cgpa,
        ':phone'  => $phone,
        ':id'     => $user['id'],
    ]);

    jsonOk(['message' => 'Profile updated successfully']);
}

jsonErr('Method not allowed', 405);
