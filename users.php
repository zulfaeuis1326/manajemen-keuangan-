<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';
requireRole(['superadmin']);

$user = currentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['superadmin', 'admin', 'user'], true) ? $_POST['role'] : 'user';

    if ($name === '' || $username === '' || strlen($password) < 4) {
        $error = 'Lengkapi semua data. Kata sandi minimal 4 karakter.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $check->execute([$username]);
        if ($check->fetch()) {
            $error = 'Username sudah dipakai, coba yang lain.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
            redirectWith('users.php', 'success', 'Pengguna berhasil ditambahkan.');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if ((int)$_POST['id'] !== (int)$user['id']) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$_POST['id']]);
        redirectWith('users.php', 'success', 'Pengguna berhasil dihapus.');
    }
}

$users = $pdo->query('SELECT * FROM users ORDER BY CASE role WHEN "superadmin" THEN 0 WHEN "admin" THEN 1 ELSE 2 END, name')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Pengguna';
$activePage = 'users';
require __DIR__ . '/includes/header.php';
?>

<div class="bg-white border border-gray-200 rounded-2xl p-5 mb-6">
  <h2 class="text-base font-bold text-ink mb-4">Tambah Pengguna</h2>
  <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <input type="hidden" name="action" value="add">
    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nama Lengkap</label>
      <input type="text" name="name" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Username</label>
      <input type="text" name="username" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kata Sandi</label>
      <input type="password" name="password" required minlength="4" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Peran</label>
      <select name="role" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        <option value="user">Pengguna — kelola transaksi sendiri</option>
        <option value="admin">Admin — kelola semua transaksi & kategori</option>
        <option value="superadmin">Superadmin — akses penuh</option>
      </select>
    </div>
    <?php if ($error): ?><p class="sm:col-span-2 text-sm text-red-600 font-medium"><?= e($error) ?></p><?php endif; ?>
    <div class="sm:col-span-2">
      <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm transition">+ Buat Akun</button>
    </div>
  </form>
</div>

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
  <div class="divide-y divide-gray-100">
    <?php foreach ($users as $u): ?>
      <div class="flex items-center justify-between px-5 py-3.5">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-brand-50 text-brand-700 flex items-center justify-center text-sm font-bold">
            <?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?>
          </div>
          <div>
            <p class="text-sm font-medium text-ink">
              <?= e($u['name']) ?>
              <?php if ((int)$u['id'] === (int)$user['id']): ?><span class="text-xs text-gray-400 font-normal">(Anda)</span><?php endif; ?>
            </p>
            <p class="text-xs text-gray-500">@<?= e($u['username']) ?> · <?= roleLabel($u['role']) ?></p>
          </div>
        </div>
        <?php if ((int)$u['id'] !== (int)$user['id']): ?>
          <form method="POST" onsubmit="return confirm('Hapus pengguna ini?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button class="text-xs font-semibold text-gray-400 hover:text-red-600">Hapus</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
