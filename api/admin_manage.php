<?php
// ============================================================
//  api/admin_manage.php  —  Admin / Holder management
//  GET  → list all admins  (holder only)
//  POST → add new admin    (holder only)
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

$actor = requireAuth('holder');
$db    = getDB();

// ── GET: list admins ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $admins = $db->query(
        'SELECT id, name, email, roll_no, dept, college, created_at
         FROM users WHERE role = "admin" ORDER BY created_at DESC'
    )->fetchAll();
    foreach ($admins as &$a) $a['id'] = (int)$a['id'];
    jsonOk(['admins' => $admins]);
}

// ── POST: create admin ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body    = getBody();
    $name    = trim($body['name']    ?? '');
    $email   = trim($body['email']   ?? '');
    $pass    = trim($body['password'] ?? '');
    $dept    = trim($body['dept']    ?? 'Training & Placement');
    $college = trim($body['college'] ?? '');

    if (!$name)  jsonErr('Name is required');
    if (!$email) jsonErr('Email is required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonErr('Invalid email');
    if (strlen($pass) < 6) jsonErr('Password must be at least 6 characters');

    $check = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $check->execute([':email' => $email]);
    if ($check->fetch()) jsonErr('Email already registered');

    $rollNo = 'ADM' . substr((string)time(), -4);
    $hash   = password_hash($pass, PASSWORD_BCRYPT);

    $db->prepare(
        'INSERT INTO users (name, email, password_hash, role, roll_no, branch, dept, college)
         VALUES (:name, :email, :hash, "admin", :roll, "Administration", :dept, :college)'
    )->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':hash'    => $hash,
        ':roll'    => $rollNo,
        ':dept'    => $dept,
        ':college' => $college,
    ]);

    jsonOk(['message' => 'Admin created successfully', 'id' => (int)$db->lastInsertId()]);
}

jsonErr('Method not allowed', 405);
