<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';
requireRole(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $type = in_array($_POST['type'] ?? '', ['income', 'expense'], true) ? $_POST['type'] : 'expense';
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO categories (name, type) VALUES (?, ?)');
            $stmt->execute([$name, $type]);
            redirectWith('kategori.php', 'success', 'Kategori berhasil ditambahkan.');
        }
    } elseif ($_POST['action'] === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$_POST['id']]);
        redirectWith('kategori.php', 'success', 'Kategori berhasil dihapus.');
    }
}

$categories = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM transactions t WHERE t.category_id = c.id) AS used_count
    FROM categories c ORDER BY c.type, c.name
")->fetchAll(PDO::FETCH_ASSOC);

$income = array_filter($categories, fn($c) => $c['type'] === 'income');
$expense = array_filter($categories, fn($c) => $c['type'] === 'expense');

$pageTitle = 'Kategori';
$activePage = 'kategori';
require __DIR__ . '/includes/header.php';
?>

<div class="bg-white border border-gray-200 rounded-2xl p-5 mb-6">
  <h2 class="text-base font-bold text-ink mb-4">Tambah Kategori</h2>
  <form method="POST" class="flex flex-wrap gap-3 items-end">
    <input type="hidden" name="action" value="add">
    <div class="flex-1 min-w-[180px]">
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nama Kategori</label>
      <input type="text" name="name" required placeholder="Contoh: Pendidikan"
             class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Jenis</label>
      <select name="type" class="px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        <option value="expense">Pengeluaran</option>
        <option value="income">Pemasukan</option>
      </select>
    </div>
    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold px-4 py-2.5 rounded-lg text-sm transition">+ Tambah</button>
  </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
  <div class="bg-white border border-gray-200 rounded-2xl p-5">
    <h3 class="text-sm font-bold text-brand-700 mb-3">Kategori Pemasukan</h3>
    <?php if (empty($income)): ?><p class="text-sm text-gray-500">Belum ada.</p><?php endif; ?>
    <?php foreach ($income as $c): ?>
      <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
        <span class="text-sm"><?= e($c['name']) ?></span>
        <div class="flex items-center gap-3">
          <span class="text-xs text-gray-400"><?= $c['used_count'] ?>x dipakai</span>
          <form method="POST" onsubmit="return confirm('Hapus kategori ini?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button class="text-xs font-semibold text-gray-400 hover:text-red-600">Hapus</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="bg-white border border-gray-200 rounded-2xl p-5">
    <h3 class="text-sm font-bold text-red-700 mb-3">Kategori Pengeluaran</h3>
    <?php if (empty($expense)): ?><p class="text-sm text-gray-500">Belum ada.</p><?php endif; ?>
    <?php foreach ($expense as $c): ?>
      <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
        <span class="text-sm"><?= e($c['name']) ?></span>
        <div class="flex items-center gap-3">
          <span class="text-xs text-gray-400"><?= $c['used_count'] ?>x dipakai</span>
          <form method="POST" onsubmit="return confirm('Hapus kategori ini?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button class="text-xs font-semibold text-gray-400 hover:text-red-600">Hapus</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
