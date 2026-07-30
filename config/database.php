<?php
/**
 * Koneksi database menggunakan SQLite.
 * Dipilih karena tidak butuh service database terpisah, tidak butuh
 * konfigurasi host/port/driver tambahan, dan file databasenya otomatis
 * dibuat sendiri saat aplikasi pertama kali jalan.
 */

// Folder tempat file database disimpan. Kalau di-deploy ke Railway,
// mount sebuah Volume ke folder ini supaya data tidak hilang saat redeploy.
$storageDir = __DIR__ . '/../storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

$dbFile = $storageDir . '/database.sqlite';
$isNew = !file_exists($dbFile);

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}

// --- Migrasi otomatis: buat tabel kalau belum ada ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('income','expense'))
)");

$pdo->exec("
CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    category_id INTEGER,
    type TEXT NOT NULL CHECK(type IN ('income','expense')),
    amount REAL NOT NULL,
    date TEXT NOT NULL,
    note TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)");

// --- Seed data awal (hanya sekali, saat tabel masih kosong) ---
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount === 0) {
    $stmt = $pdo->prepare('INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)');
    $stmt->execute(['Super Admin', 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'superadmin']);
}

$catCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
if ($catCount === 0) {
    $defaults = [
        ['Gaji', 'income'],
        ['Usaha Sampingan', 'income'],
        ['Lainnya (Masuk)', 'income'],
        ['Makan & Minum', 'expense'],
        ['Transportasi', 'expense'],
        ['Tagihan', 'expense'],
        ['Belanja', 'expense'],
        ['Lainnya (Keluar)', 'expense'],
    ];
    $stmt = $pdo->prepare('INSERT INTO categories (name, type) VALUES (?, ?)');
    foreach ($defaults as $c) {
        $stmt->execute($c);
    }
}
