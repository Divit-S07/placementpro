<?php
// ============================================================
//  api/delete_account.php  —  POST  Permanently delete account
//  Deletes: user row, exam_results, completed_rounds, sessions
//  (CASCADE handles DB children; we still delete session here)
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('Method not allowed', 405);

$user = requireAuth('student');   // only students can self-delete
$db   = getDB();

// The FOREIGN KEY ON DELETE CASCADE will auto-remove:
//   exam_results, completed_rounds, sessions
$stmt = $db->prepare('DELETE FROM users WHERE id = :id AND role = "student"');
$stmt->execute([':id' => $user['id']]);

if ($stmt->rowCount() === 0) {
    jsonErr('Account not found or already deleted');
}

jsonOk(['message' => 'Account and all associated data permanently deleted']);
