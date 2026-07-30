<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan kata sandi wajib diisi.';
    } elseif (attemptLogin($pdo, $username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau kata sandi salah.';
    }
}

// Beri tahu apakah ini masih akun superadmin bawaan (belum pernah login lain)
$onlyDefaultAdmin = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — Keuangan Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script>
  tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter','sans-serif'] },
    colors: { brand: { 500:'#219563', 600:'#15794f', 700:'#106140', 900:'#0d3f2c' } } } } }
</script>
<style>body{font-family:'Inter',sans-serif;background:#F6F7F5;}</style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <div class="w-12 h-12 rounded-xl bg-brand-600 text-white font-extrabold flex items-center justify-center mx-auto mb-4">Rp</div>
      <h1 class="text-2xl font-bold text-gray-900">Keuangan Panel</h1>
      <p class="text-sm text-gray-500 mt-1">Masuk untuk mengelola keuangan</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1.5">Username</label>
          <input type="text" name="username" autofocus required
                 class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kata Sandi</label>
          <input type="password" name="password" required
                 class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
        </div>
        <?php if ($error): ?>
          <p class="text-sm text-red-600 font-medium"><?= e($error) ?></p>
        <?php endif; ?>
        <button type="submit"
                class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
          Masuk
        </button>
      </form>
    </div>

    <?php if ($onlyDefaultAdmin): ?>
      <div class="mt-4 text-xs text-gray-500 text-center bg-white border border-gray-200 rounded-xl px-4 py-3">
        Akun superadmin awal — Username: <b>admin</b> · Kata sandi: <b>admin123</b><br>
        Segera ganti kata sandi setelah masuk pertama kali.
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
