<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();
$scopeAll = in_array($user['role'], ['superadmin', 'admin'], true);

$month = (int) ($_GET['month'] ?? date('n'));
$year = (int) ($_GET['year'] ?? date('Y'));

$whereScope = $scopeAll ? '1=1' : 't.user_id = :uid';
$baseParams = $scopeAll ? [] : ['uid' => $user['id']];

$availableYears = $pdo->query("SELECT DISTINCT strftime('%Y', date) AS y FROM transactions ORDER BY y DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($availableYears)) $availableYears = [date('Y')];
if (!in_array((string)$year, $availableYears, true)) $availableYears[] = (string)$year;
rsort($availableYears);

$mParams = array_merge($baseParams, ['m' => str_pad($month, 2, '0', STR_PAD_LEFT), 'y' => $year]);
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions t WHERE type='income' AND $whereScope AND strftime('%m', date)=:m AND strftime('%Y', date)=:y");
$stmt->execute($mParams);
$monthIncome = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions t WHERE type='expense' AND $whereScope AND strftime('%m', date)=:m AND strftime('%Y', date)=:y");
$stmt->execute($mParams);
$monthExpense = (float) $stmt->fetchColumn();

// Pengeluaran per kategori bulan ini
$stmt = $pdo->prepare("
    SELECT c.name, COALESCE(SUM(t.amount),0) AS total
    FROM transactions t LEFT JOIN categories c ON c.id = t.category_id
    WHERE t.type='expense' AND $whereScope AND strftime('%m', t.date)=:m AND strftime('%Y', t.date)=:y
    GROUP BY c.name HAVING total > 0 ORDER BY total DESC
");
$stmt->execute($mParams);
$byCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tren tahunan
$yearTrend = [];
for ($m = 1; $m <= 12; $m++) {
    $p = array_merge($baseParams, ['m' => str_pad($m, 2, '0', STR_PAD_LEFT), 'y' => $year]);
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions t WHERE type='income' AND $whereScope AND strftime('%m', date)=:m AND strftime('%Y', date)=:y");
    $stmt->execute($p);
    $inc = (float) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions t WHERE type='expense' AND $whereScope AND strftime('%m', date)=:m AND strftime('%Y', date)=:y");
    $stmt->execute($p);
    $exp = (float) $stmt->fetchColumn();
    $yearTrend[] = ['label' => substr(monthNameId($m), 0, 3), 'income' => $inc, 'expense' => $exp];
}

// Rincian transaksi bulan ini
$sqlDetail = "SELECT t.*, c.name AS category_name FROM transactions t LEFT JOIN categories c ON c.id=t.category_id WHERE $whereScope AND strftime('%m', t.date)=:m AND strftime('%Y', t.date)=:y ORDER BY t.date DESC";
$stmt = $pdo->prepare($sqlDetail);
$stmt->execute($mParams);
$detail = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Laporan';
$activePage = 'laporan';
require __DIR__ . '/includes/header.php';
?>

<form method="GET" class="flex flex-wrap gap-3 mb-5">
  <select name="month" onchange="this.form.submit()" class="px-3.5 py-2 border border-gray-300 rounded-lg text-sm">
    <?php for ($m = 1; $m <= 12; $m++): ?>
      <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= monthNameId($m) ?></option>
    <?php endfor; ?>
  </select>
  <select name="year" onchange="this.form.submit()" class="px-3.5 py-2 border border-gray-300 rounded-lg text-sm">
    <?php foreach ($availableYears as $y): ?>
      <option value="<?= $y ?>" <?= (int)$y === $year ? 'selected' : '' ?>><?= $y ?></option>
    <?php endforeach; ?>
  </select>
</form>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-2xl p-5">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Pemasukan <?= monthNameId($month) ?></p>
    <p class="num text-xl font-bold text-brand-600 border-t border-gray-100 pt-3"><?= rupiah($monthIncome) ?></p>
  </div>
  <div class="bg-white border border-gray-200 rounded-2xl p-5">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Pengeluaran <?= monthNameId($month) ?></p>
    <p class="num text-xl font-bold text-red-600 border-t border-gray-100 pt-3"><?= rupiah($monthExpense) ?></p>
  </div>
  <div class="bg-white border border-gray-200 rounded-2xl p-5">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Selisih Bulan Ini</p>
    <p class="num text-xl font-bold text-ink border-t border-gray-100 pt-3"><?= rupiah($monthIncome - $monthExpense) ?></p>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
  <div class="bg-white border border-gray-200 rounded-2xl p-5">
    <h3 class="text-sm font-bold text-ink mb-4">Pengeluaran per Kategori</h3>
    <?php if (empty($byCategory)): ?>
      <p class="text-sm text-gray-500 py-8 text-center">Belum ada pengeluaran pada bulan ini.</p>
    <?php else: ?>
      <canvas id="pieChart" height="220"></canvas>
    <?php endif; ?>
  </div>
  <div class="bg-white border border-gray-200 rounded-2xl p-5">
    <h3 class="text-sm font-bold text-ink mb-4">Tren Tahun <?= $year ?></h3>
    <canvas id="lineChart" height="220"></canvas>
  </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <h3 class="text-sm font-bold text-ink">Rincian Transaksi Bulan Ini</h3>
  </div>
  <?php if (empty($detail)): ?>
    <p class="text-sm text-gray-500 py-8 text-center">Tidak ada transaksi pada bulan ini.</p>
  <?php else: ?>
    <div class="divide-y divide-gray-100">
      <?php foreach ($detail as $t): ?>
        <div class="flex justify-between px-5 py-2.5 text-sm">
          <span class="text-gray-500"><?= date('d M', strtotime($t['date'])) ?> · <?= e($t['category_name'] ?? '-') ?></span>
          <span class="num font-semibold <?= $t['type'] === 'income' ? 'text-brand-600' : 'text-red-600' ?>">
            <?= $t['type'] === 'income' ? '+' : '−' ?> <?= rupiah($t['amount']) ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
<?php if (!empty($byCategory)): ?>
  new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(array_column($byCategory, 'name')) ?>,
      datasets: [{ data: <?= json_encode(array_column($byCategory, 'total')) ?>,
        backgroundColor: ['#219563','#b8892b','#3e7c5a','#dc2626','#6b7f6e','#d6a94a','#7a4b32','#4c6e5a'] }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
  });
<?php endif; ?>

  const yearTrend = <?= json_encode($yearTrend) ?>;
  new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
      labels: yearTrend.map(t => t.label),
      datasets: [
        { label: 'Pemasukan', data: yearTrend.map(t => t.income), borderColor: '#219563', backgroundColor: '#21956320', tension: 0.3 },
        { label: 'Pengeluaran', data: yearTrend.map(t => t.expense), borderColor: '#dc2626', backgroundColor: '#dc262620', tension: 0.3 },
      ]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
      scales: { y: { ticks: { callback: v => (v/1000000) + 'jt' } } } }
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
