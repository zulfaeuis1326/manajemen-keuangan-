<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: transaksi.php');
    exit;
}

$user = currentUser();
$scopeAll = in_array($user['role'], ['superadmin', 'admin'], true);

$stmt = $pdo->prepare('SELECT user_id FROM transactions WHERE id = ?');
$stmt->execute([$_POST['id']]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing && ($scopeAll || $existing['user_id'] == $user['id'])) {
    $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = ?');
    $stmt->execute([$_POST['id']]);
    redirectWith('transaksi.php', 'success', 'Transaksi berhasil dihapus.');
}

redirectWith('transaksi.php', 'error', 'Transaksi tidak ditemukan.');
