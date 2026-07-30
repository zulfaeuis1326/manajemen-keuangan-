<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: transaksi.php');
    exit;
}

$user = currentUser();
$scopeAll = in_array($user['role'], ['superadmin', 'admin'], true);

$type = in_array($_POST['type'] ?? '', ['income', 'expense'], true) ? $_POST['type'] : 'expense';
$amount = (float) ($_POST['amount'] ?? 0);
$categoryId = (int) ($_POST['category_id'] ?? 0);
$date = $_POST['date'] ?? date('Y-m-d');
$note = trim($_POST['note'] ?? '');

if ($amount <= 0 || $categoryId <= 0) {
    redirectWith('transaksi_form.php', 'error', 'Jumlah dan kategori wajib diisi dengan benar.');
}

if (!empty($_POST['id'])) {
    // Update — pastikan pengguna biasa hanya bisa ubah transaksi miliknya sendiri
    $stmt = $pdo->prepare('SELECT user_id FROM transactions WHERE id = ?');
    $stmt->execute([$_POST['id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing || (!$scopeAll && $existing['user_id'] != $user['id'])) {
        redirectWith('transaksi.php', 'error', 'Transaksi tidak ditemukan.');
    }

    $stmt = $pdo->prepare('UPDATE transactions SET type=?, amount=?, category_id=?, date=?, note=? WHERE id=?');
    $stmt->execute([$type, $amount, $categoryId, $date, $note, $_POST['id']]);
    redirectWith('transaksi.php', 'success', 'Transaksi berhasil diperbarui.');
} else {
    $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, category_id, date, note) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user['id'], $type, $amount, $categoryId, $date, $note]);
    redirectWith('transaksi.php', 'success', 'Transaksi berhasil ditambahkan.');
}
