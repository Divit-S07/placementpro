<?php
// config/helpers.php — Shared utilities
require_once __DIR__ . '/db.php';

// JSON + CORS headers — must come before ANY output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Token, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Helpers ───────────────────────────────────────────────────
function jsonOk(array $data = []): void {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function jsonErr(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function getBody(): array {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function getTokenFromRequest(): ?string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    // Normalize header keys to Title-Case
    $normalized = [];
    foreach ($headers as $k => $v) {
        $normalized[strtolower($k)] = $v;
    }
    if (!empty($normalized['x-token'])) return trim($normalized['x-token']);
    if (!empty($normalized['authorization'])) {
        return trim(str_replace('Bearer', '', $normalized['authorization']));
    }
    // Also check $_SERVER as fallback
    if (!empty($_SERVER['HTTP_X_TOKEN'])) return trim($_SERVER['HTTP_X_TOKEN']);
    return null;
}

function requireAuth(?string $requiredRole = null): array {
    $token = getTokenFromRequest();
    if (!$token) jsonErr('Not logged in. Please sign in again.', 401);

    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT u.* FROM sessions s
         JOIN users u ON u.id = s.user_id
         WHERE s.token = :token AND s.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) jsonErr('Session expired. Please sign in again.', 401);
    if ($requiredRole && $user['role'] !== $requiredRole) {
        jsonErr('Access denied.', 403);
    }
    return $user;
}

function generateToken(): string {
    return bin2hex(random_bytes(48));
}

function sessionExpiry(): string {
    return date('Y-m-d H:i:s', strtotime('+7 days'));
}
