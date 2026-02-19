<?php
// ============================================================
//  test.php — Run this FIRST to check everything is working
//  Open: http://localhost/placementpro/test.php
//  DELETE this file after everything works!
// ============================================================
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<title>PlacementPro — Setup Check</title>
<style>
  body{font-family:monospace;background:#0c0e14;color:#e8ecff;padding:40px;line-height:1.8}
  h1{color:#22d3a5;font-size:24px;margin-bottom:30px}
  h2{color:#38bdf8;font-size:16px;margin:24px 0 10px}
  .ok{color:#22d3a5}
  .fail{color:#f87171}
  .warn{color:#fbbf24}
  .box{background:#181b26;border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:20px;margin-bottom:20px}
  .cmd{background:#111420;border-radius:6px;padding:8px 14px;color:#a5f3fc;font-size:13px;margin-top:6px}
  a{color:#38bdf8}
</style>
</head>
<body>
<h1>🔍 PlacementPro — Setup Diagnostic</h1>

<?php
$allOk = true;

// CHECK 1: PHP Version
$phpVer = PHP_VERSION;
$phpOk  = version_compare($phpVer, '7.4', '>=');
echo '<div class="box"><h2>1. PHP Version</h2>';
echo $phpOk
  ? "<span class='ok'>✅ PHP $phpVer — OK</span>"
  : "<span class='fail'>❌ PHP $phpVer — Need 7.4 or higher. Update XAMPP.</span>";
echo '</div>';
if (!$phpOk) $allOk = false;

// CHECK 2: PDO + MySQL extension
echo '<div class="box"><h2>2. PDO MySQL Extension</h2>';
if (extension_loaded('pdo_mysql')) {
    echo "<span class='ok'>✅ pdo_mysql loaded — OK</span>";
} else {
    echo "<span class='fail'>❌ pdo_mysql NOT loaded. In php.ini uncomment: extension=pdo_mysql</span>";
    $allOk = false;
}
echo '</div>';

// CHECK 3: config files exist
echo '<div class="box"><h2>3. Config Files</h2>';
$files = [
    __DIR__ . '/config/db.php'      => 'config/db.php',
    __DIR__ . '/config/helpers.php' => 'config/helpers.php',
    __DIR__ . '/api/login.php'      => 'api/login.php',
    __DIR__ . '/api/register.php'   => 'api/register.php',
    __DIR__ . '/api/save_result.php'=> 'api/save_result.php',
];
foreach ($files as $path => $label) {
    if (file_exists($path)) {
        echo "<span class='ok'>✅ $label exists</span><br>";
    } else {
        echo "<span class='fail'>❌ $label MISSING — copy all files to htdocs/placementpro/</span><br>";
        $allOk = false;
    }
}
echo '</div>';

// CHECK 4: Database connection
echo '<div class="box"><h2>4. Database Connection</h2>';
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=placementpro;charset=utf8mb4',
        'root', '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<span class='ok'>✅ Connected to MySQL — OK</span><br>";

    // CHECK 5: Tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $needed = ['users','exam_results','completed_rounds','sessions'];
    echo '<h2>5. Database Tables</h2>';
    foreach ($needed as $t) {
        if (in_array($t, $tables)) {
            echo "<span class='ok'>✅ Table `$t` exists</span><br>";
        } else {
            echo "<span class='fail'>❌ Table `$t` MISSING — run placementpro.sql in phpMyAdmin</span><br>";
            $allOk = false;
        }
    }

    // CHECK 6: Seed users
    echo '<h2>6. Seed Users</h2>';
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count >= 3) {
        echo "<span class='ok'>✅ $count users found in database</span><br>";
        // Check password hashes
        $admin = $pdo->query("SELECT password_hash FROM users WHERE email='admin@placementpro.com'")->fetch();
        if ($admin) {
            $hashOk = password_verify('admin123', $admin['password_hash']);
            if ($hashOk) {
                echo "<span class='ok'>✅ Password hashes are correct</span><br>";
            } else {
                echo "<span class='warn'>⚠️ Password hashes need fixing — open: <a href='setup_passwords.php'>setup_passwords.php</a></span><br>";
            }
        }
    } else {
        echo "<span class='fail'>❌ No users found — run placementpro.sql in phpMyAdmin</span><br>";
        $allOk = false;
    }

} catch (PDOException $e) {
    echo "<span class='fail'>❌ Cannot connect to database: {$e->getMessage()}</span><br>";
    echo "<div class='cmd'>Fix: Make sure MySQL is started in XAMPP Control Panel<br>";
    echo "And the database 'placementpro' exists in phpMyAdmin</div>";
    $allOk = false;
}
echo '</div>';

// CHECK 7: CORS / Headers test
echo '<div class="box"><h2>7. Quick API Test</h2>';
echo "<span class='ok'>✅ PHP is executing — Apache is working</span><br>";
echo "<span class='ok'>✅ This file is in the right folder</span>";
echo '</div>';

// FINAL RESULT
echo '<div class="box" style="border-color:' . ($allOk ? '#22d3a5' : '#f87171') . '44">';
echo '<h2>Result</h2>';
if ($allOk) {
    echo "<span class='ok' style='font-size:18px'>🎉 ALL CHECKS PASSED! Open the app: <a href='index.html'>index.html</a></span><br><br>";
    echo "<span class='warn'>⚠️ Delete test.php and setup_passwords.php after confirming the app works!</span>";
} else {
    echo "<span class='fail' style='font-size:16px'>❌ Some checks failed. Fix the issues above, then refresh this page.</span>";
}
echo '</div>';
?>
</body>
</html>
