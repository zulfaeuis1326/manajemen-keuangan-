<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();
$scopeAll = in_array($user['role'], ['superadmin', 'admin'], true);
$filter = $_GET['type'] ?? 'all';

$sql = "SELECT t.*, c.name AS category_name FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE 1=1";
$params = [];
if (!$scopeAll) {
    $sql .= " AND t.user_id = :uid";
    $params['uid'] = $user['id'];
}
if (in_array($filter, ['income', 'expense'], true)) {
    $sql .= " AND t.type = :type";
    $params['type'] = $filter;
}
$sql .= " ORDER BY t.date DESC, t.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Transaksi';
$activePage = 'transaksi';
require __DIR__ . '/includes/header.php';
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
  <div class="flex gap-2">
    <?php foreach (['all' => 'Semua', 'income' => 'Pemasukan', 'expense' => 'Pengeluaran'] as $key => $label): ?>
      <a href="?type=<?= $key ?>"
         class="px-3.5 py-1.5 rounded-full text-xs font-semibold border transition
                <?= $filter === $key ? 'bg-brand-600 text-white border-brand-600' : 'text-gray-600 border-gray-300 hover:bg-gray-50' ?>">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>
  <a href="transaksi_form.php"
     class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
    + Tambah Transaksi
  </a>
</div>

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
  <?php if (empty($transactions)): ?>
    <p class="text-sm text-gray-500 py-10 text-center">Belum ada transaksi pada filter ini.</p>
  <?php else: ?>
    <div class="divide-y divide-gray-100">
      <?php foreach ($transactions as $t): ?>
        <div class="flex items-center justify-between px-5 py-3.5">
          <div>
            <p class="text-sm font-medium text-ink"><?= e($t['category_name'] ?? 'Tanpa kategori') ?></p>
            <p class="text-xs text-gray-500">
              <?= date('d M Y', strtotime($t['date'])) ?><?= $t['note'] ? ' · ' . e($t['note']) : '' ?>
            </p>
          </div>
          <div class="flex items-center gap-4">
            <span class="num text-sm font-semibold <?= $t['type'] === 'income' ? 'text-brand-600' : 'text-red-600' ?>">
              <?= $t['type'] === 'income' ? '+' : '−' ?> <?= rupiah($t['amount']) ?>
            </span>
            <a href="transaksi_form.php?id=<?= $t['id'] ?>" class="text-gray-400 hover:text-brand-600 text-xs font-semibold">Ubah</a>
            <form method="POST" action="transaksi_hapus.php" onsubmit="return confirm('Hapus transaksi ini?');">
              <input type="hidden" name="id" value="<?= $t['id'] ?>">
              <button type="submit" class="text-gray-400 hover:text-red-600 text-xs font-semibold">Hapus</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
