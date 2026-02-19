<?php
// ============================================================
//  api/register.php  —  POST  Student registration
// ============================================================
//  Expected JSON body:
//  {
//    "name"    : "Arjun Sharma",
//    "email"   : "arjun@student.com",
//    "password": "mypassword",
//    "roll_no" : "CS2021001",
//    "branch"  : "Computer Science",
//    "cgpa"    : 8.5
//  }
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErr('Method not allowed', 405);
}

$body = getBody();

// ── Validate required fields ──────────────────────────────────
$name     = trim($body['name']     ?? '');
$email    = trim($body['email']    ?? '');
$password = trim($body['password'] ?? '');
$rollNo   = trim($body['roll_no']  ?? '');
$branch   = trim($body['branch']   ?? 'Computer Science');
$cgpa     = isset($body['cgpa']) ? (float)$body['cgpa'] : null;

if (!$name)     jsonErr('Name is required');
if (!$email)    jsonErr('Email is required');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonErr('Invalid email address');
if (!$password) jsonErr('Password is required');
if (strlen($password) < 6) jsonErr('Password must be at least 6 characters');
if (!$rollNo)   jsonErr('Roll number is required');

$db = getDB();

// ── Check duplicate email ─────────────────────────────────────
$stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    jsonErr('Email is already registered');
}

// ── Insert new student ────────────────────────────────────────
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $db->prepare(
    'INSERT INTO users (name, email, password_hash, role, roll_no, branch, cgpa)
     VALUES (:name, :email, :hash, "student", :roll_no, :branch, :cgpa)'
);
$stmt->execute([
    ':name'    => $name,
    ':email'   => $email,
    ':hash'    => $hash,
    ':roll_no' => $rollNo,
    ':branch'  => $branch,
    ':cgpa'    => $cgpa,
]);

$userId = (int)$db->lastInsertId();

// ── Create session automatically (auto-login after register) ──
$token = generateToken();
$db->prepare(
    'INSERT INTO sessions (user_id, token, expires_at) VALUES (:uid, :token, :exp)'
)->execute([':uid' => $userId, ':token' => $token, ':exp' => sessionExpiry()]);

jsonOk([
    'token' => $token,
    'user'  => [
        'id'       => $userId,
        'name'     => $name,
        'email'    => $email,
        'role'     => 'student',
        'roll_no'  => $rollNo,
        'branch'   => $branch,
        'cgpa'     => $cgpa,
        'phone'    => null,
    ],
]);
