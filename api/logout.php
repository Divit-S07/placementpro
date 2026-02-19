<?php
// ============================================================
//  api/logout.php  —  POST  Destroy session
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

$token = getTokenFromRequest();
if ($token) {
    getDB()->prepare('DELETE FROM sessions WHERE token = :token')
           ->execute([':token' => $token]);
}
jsonOk(['message' => 'Logged out successfully']);
