<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();
$scopeAll = in_array($user['role'], ['superadmin', 'admin'], true);

$where = $scopeAll ? '1=1' : 'user_id = :uid';
$params = $scopeAll ? [] : ['uid' => $user['id']];

$totalIncome = (float) queryScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income' AND $where", $params);
$totalExpense = (float) queryScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense' AND $where", $params);
$saldo = $totalIncome - $totalExpense;

$curMonth = date('m');
$curYear = date('Y');
$paramsMonth = array_merge($params, ['m' => $curMonth, 'y' => $curYear]);
$monthIncome = (float) queryScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income' AND $where AND strftime('%m', date) = :m AND strftime('%Y', date) = :y", $paramsMonth);
$monthExpense = (float) queryScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense' AND $where AND strftime('%m', date) = :m AND strftime('%Y', date) = :y", $paramsMonth);

// Tren 6 bulan terakhir
$trend = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("-$i months");
    $m = date('m', $ts);
    $y = date('Y', $ts);
    $p = array_merge($params, ['m' => $m, 'y' => $y]);
    $inc = (float) queryScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income' AND $where AND strftime('%m', date) = :m AND strftime('%Y', date) = :y", $p);
    $exp = (float) queryScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense' AND $where AND strftime('%m', date) = :m AND strftime('%Y', date) = :y", $p);
    $trend[] = ['label' => monthNameId((int)$m) . ' ' . substr($y, 2), 'income' => $inc, 'expense' => $exp];
}

// Transaksi terbaru
$sqlRecent = "SELECT t.*, c.name AS category_name FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE $where ORDER BY t.date DESC, t.id DESC LIMIT 6";
$stmt = $pdo->prepare($sqlRecent);
$stmt->execute($params);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

function queryScalar(PDO $pdo, string $sql, array $params)
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

$pageTitle = 'Dasbor';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-2xl p-5">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Saldo Total</p>
    <p class="num text-2xl font-bold text-ink border-t border-gray-100 pt-3"><?= rupiah($saldo) ?></p>
  </div>
  <div class="bg-white border border-gray-200 rounded-2xl p-5">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Pemasukan Bulan Ini</p>
    <p class="num text-2xl font-bold text-brand-600 border-t border-gray-100 pt-3"><?= rupiah($monthIncome) ?></p>
  </div>
  <div class="bg-white border border-gray-200 rounded-2xl p-5">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Pengeluaran Bulan Ini</p>
    <p class="num text-2xl font-bold text-red-600 border-t border-gray-100 pt-3"><?= rupiah($monthExpense) ?></p>
  </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl p-5 mb-6">
  <h2 class="text-base font-bold text-ink mb-4">Tren 6 Bulan Terakhir</h2>
  <canvas id="trendChart" height="90"></canvas>
</div>

<div class="bg-white border border-gray-200 rounded-2xl p-5">
  <h2 class="text-base font-bold text-ink mb-3">Transaksi Terbaru</h2>
  <?php if (empty($recent)): ?>
    <p class="text-sm text-gray-500 py-6 text-center">Belum ada transaksi. Tambahkan lewat menu Transaksi.</p>
  <?php else: ?>
    <div class="divide-y divide-gray-100">
      <?php foreach ($recent as $t): ?>
        <div class="flex items-center justify-between py-3">
          <div>
            <p class="text-sm font-medium text-ink"><?= e($t['category_name'] ?? 'Tanpa kategori') ?></p>
            <p class="text-xs text-gray-500"><?= date('d M Y', strtotime($t['date'])) ?><?= $t['note'] ? ' · ' . e($t['note']) : '' ?></p>
          </div>
          <span class="num text-sm font-semibold <?= $t['type'] === 'income' ? 'text-brand-600' : 'text-red-600' ?>">
            <?= $t['type'] === 'income' ? '+' : '−' ?> <?= rupiah($t['amount']) ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
  const trend = <?= json_encode($trend) ?>;
  new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
      labels: trend.map(t => t.label),
      datasets: [
        { label: 'Pemasukan', data: trend.map(t => t.income), backgroundColor: '#219563', borderRadius: 6 },
        { label: 'Pengeluaran', data: trend.map(t => t.expense), backgroundColor: '#dc2626', borderRadius: 6 },
      ]
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter' } } } },
      scales: { y: { ticks: { callback: v => (v/1000000) + 'jt' } } }
    }
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
