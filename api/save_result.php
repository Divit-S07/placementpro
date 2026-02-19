<?php
// ============================================================
//  api/save_result.php  —  POST  Save exam result after submit
// ============================================================
//  Expected JSON body:
//  {
//    "company"   : "TCS",
//    "round"     : "Aptitude",
//    "score"     : 80,         // percentage
//    "total_q"   : 10,
//    "correct_q" : 8,
//    "passed"    : true
//  }
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('Method not allowed', 405);

$user = requireAuth('student');
$db   = getDB();
$body = getBody();

$company   = trim($body['company']   ?? '');
$round     = trim($body['round']     ?? '');
$score     = isset($body['score'])     ? (int)$body['score']     : 0;
$totalQ    = isset($body['total_q'])   ? (int)$body['total_q']   : 0;
$correctQ  = isset($body['correct_q']) ? (int)$body['correct_q'] : 0;
$passed    = isset($body['passed'])    ? (bool)$body['passed']   : ($score >= 50);

if (!$company) jsonErr('Company is required');
if (!$round)   jsonErr('Round is required');

// ── Insert exam result (allow retakes — multiple rows kept) ───
$db->prepare(
    'INSERT INTO exam_results (user_id, company, round, score, total_q, correct_q, passed)
     VALUES (:uid, :company, :round, :score, :tq, :cq, :passed)'
)->execute([
    ':uid'     => $user['id'],
    ':company' => $company,
    ':round'   => $round,
    ':score'   => $score,
    ':tq'      => $totalQ,
    ':cq'      => $correctQ,
    ':passed'  => $passed ? 1 : 0,
]);

// ── Mark round as completed (upsert) ─────────────────────────
$db->prepare(
    'INSERT IGNORE INTO completed_rounds (user_id, company, round)
     VALUES (:uid, :company, :round)'
)->execute([
    ':uid'     => $user['id'],
    ':company' => $company,
    ':round'   => $round,
]);

jsonOk([
    'message'  => 'Result saved successfully',
    'score'    => $score,
    'passed'   => $passed,
]);
