<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Batasi akses halaman hanya untuk peran tertentu.
 * Contoh: requireRole(['superadmin', 'admin']);
 */
function requireRole(array $roles): void
{
    requireLogin();
    if (!in_array(currentUser()['role'], $roles, true)) {
        http_response_code(403);
        die('Akses ditolak. Halaman ini khusus untuk peran: ' . implode(', ', $roles));
    }
}

function attemptLogin(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

function logoutUser(): void
{
    $_SESSION = [];
    session_destroy();
}

function roleLabel(string $role): string
{
    return match ($role) {
        'superadmin' => 'Superadmin',
        'admin' => 'Admin',
        default => 'Pengguna',
    };
}
