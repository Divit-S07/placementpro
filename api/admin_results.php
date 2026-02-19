<?php
// ============================================================
//  api/admin_results.php  —  GET  All exam results (admin view)
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonErr('Method not allowed', 405);

$admin = requireAuth();
if (!in_array($admin['role'], ['admin', 'holder'])) jsonErr('Forbidden', 403);

$db = getDB();

$results = $db->query(
    'SELECT er.id, er.company, er.round, er.score, er.total_q,
            er.correct_q, er.passed, er.taken_at,
            u.name, u.roll_no, u.branch, u.email
     FROM exam_results er
     JOIN users u ON u.id = er.user_id
     ORDER BY er.taken_at DESC'
)->fetchAll();

// Cast types
foreach ($results as &$r) {
    $r['id']        = (int)$r['id'];
    $r['score']     = (int)$r['score'];
    $r['total_q']   = (int)$r['total_q'];
    $r['correct_q'] = (int)$r['correct_q'];
    $r['passed']    = (bool)$r['passed'];
}

jsonOk(['results' => $results, 'total' => count($results)]);
