<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();
$scopeAll = in_array($user['role'], ['superadmin', 'admin'], true);

$editing = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editing || (!$scopeAll && $editing['user_id'] != $user['id'])) {
        redirectWith('transaksi.php', 'error', 'Transaksi tidak ditemukan.');
    }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY type, name')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $editing ? 'Ubah Transaksi' : 'Tambah Transaksi';
$activePage = 'transaksi';
require __DIR__ . '/includes/header.php';
?>

<div class="bg-white border border-gray-200 rounded-2xl p-6 max-w-lg">
  <form method="POST" action="transaksi_proses.php" class="space-y-4">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Jenis</label>
      <div class="grid grid-cols-2 gap-2">
        <?php foreach (['expense' => 'Pengeluaran', 'income' => 'Pemasukan'] as $val => $label):
          $checked = ($editing['type'] ?? 'expense') === $val; ?>
          <label class="flex items-center justify-center gap-2 border rounded-lg py-2.5 text-sm font-semibold cursor-pointer transition
                        <?= $checked ? ($val === 'income' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-red-500 bg-red-50 text-red-700') : 'border-gray-300 text-gray-500' ?>">
            <input type="radio" name="type" value="<?= $val ?>" class="hidden" <?= $checked ? 'checked' : '' ?> onchange="this.form.requestSubmit ? null : null">
            <?= $label ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Jumlah (Rp)</label>
      <input type="number" name="amount" min="0" step="1" required value="<?= e((string)($editing['amount'] ?? '')) ?>"
             class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>

    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kategori</label>
      <select name="category_id" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" data-type="<?= $c['type'] ?>" <?= ($editing['category_id'] ?? null) == $c['id'] ? 'selected' : '' ?>>
            <?= e($c['name']) ?> (<?= $c['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran' ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tanggal</label>
      <input type="date" name="date" required value="<?= e($editing['date'] ?? date('Y-m-d')) ?>"
             class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>

    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1.5">Catatan (opsional)</label>
      <input type="text" name="note" value="<?= e($editing['note'] ?? '') ?>" placeholder="Contoh: Belanja bulanan"
             class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>

    <div class="flex gap-3 pt-2">
      <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">Simpan</button>
      <a href="transaksi.php" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-50 text-center">Batal</a>
    </div>
  </form>
</div>

<script>
  // Klik label jenis transaksi supaya radio ikut ke-select (radio disembunyikan via Tailwind)
  document.querySelectorAll('label:has(input[name="type"])').forEach(label => {
    label.addEventListener('click', () => {
      document.querySelectorAll('label:has(input[name="type"])').forEach(l => {
        l.classList.remove('border-brand-600','bg-brand-50','text-brand-700','border-red-500','bg-red-50','text-red-700');
        l.classList.add('border-gray-300','text-gray-500');
      });
      const input = label.querySelector('input');
      input.checked = true;
      label.classList.remove('border-gray-300','text-gray-500');
      if (input.value === 'income') label.classList.add('border-brand-600','bg-brand-50','text-brand-700');
      else label.classList.add('border-red-500','bg-red-50','text-red-700');
    });
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
