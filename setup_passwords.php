<?php
// setup_passwords.php — Run ONCE, then DELETE this file!
// Open: http://localhost/placementpro/setup_passwords.php
header('Content-Type: text/html; charset=utf-8');

// Use __DIR__ so the path works no matter where PHP is called from
require_once __DIR__ . '/config/db.php';

$db = getDB();

$accounts = [
    ['admin@placementpro.com',  'admin123'],
    ['arjun@student.com',       'student123'],
    ['holder@placementpro.com', 'holder123'],
];

echo '<div style="font-family:monospace;background:#0c0e14;color:#e8ecff;padding:40px;font-size:15px;line-height:2">';
echo '<h2 style="color:#22d3a5;margin-bottom:20px">Setting up password hashes...</h2>';

foreach ($accounts as [$email, $pass]) {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $stmt->execute([$hash, $email]);
    if ($stmt->rowCount() > 0) {
        echo "✅ <span style='color:#22d3a5'>Updated:</span> $email<br>";
    } else {
        echo "⚠️ <span style='color:#fbbf24'>Not found (run placementpro.sql first):</span> $email<br>";
    }
}

echo '<br><strong style="color:#fbbf24;font-size:16px">✅ Done! Delete this file now.</strong><br>';
echo '<br><a style="color:#38bdf8;font-size:15px" href="index.html">→ Go to the App</a></div>';
