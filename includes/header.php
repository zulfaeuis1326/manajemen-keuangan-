<?php
$user = currentUser();
$flash = getFlash();
$activePage = $activePage ?? '';

$menuItems = [
    ['key' => 'dashboard', 'label' => 'Dasbor', 'url' => 'dashboard.php', 'roles' => ['superadmin', 'admin', 'user']],
    ['key' => 'transaksi', 'label' => 'Transaksi', 'url' => 'transaksi.php', 'roles' => ['superadmin', 'admin', 'user']],
    ['key' => 'kategori', 'label' => 'Kategori', 'url' => 'kategori.php', 'roles' => ['superadmin', 'admin']],
    ['key' => 'laporan', 'label' => 'Laporan', 'url' => 'laporan.php', 'roles' => ['superadmin', 'admin', 'user']],
    ['key' => 'users', 'label' => 'Pengguna', 'url' => 'users.php', 'roles' => ['superadmin']],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>Keuangan Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['Inter','sans-serif'], mono: ['JetBrains Mono','monospace'] },
        colors: {
          brand: { 50:'#eefaf3',100:'#d6f2e2',200:'#aee4c8',300:'#78cea6',400:'#43b17f',500:'#219563',600:'#15794f',700:'#106140',800:'#0f4d34',900:'#0d3f2c' },
          ink: '#111827',
        }
      }
    }
  }
</script>
<style>
  body { font-family: 'Inter', sans-serif; background:#F6F7F5; }
  .num { font-family: 'JetBrains Mono', monospace; font-variant-numeric: tabular-nums; }
  ::-webkit-scrollbar { width:8px; height:8px; }
  ::-webkit-scrollbar-thumb { background:#cbd5c9; border-radius:8px; }
</style>
</head>
<body class="text-ink">
<div class="min-h-screen flex">

  <!-- Sidebar -->
  <aside class="w-64 shrink-0 bg-brand-900 text-white flex flex-col fixed inset-y-0 left-0 z-30 -translate-x-full lg:translate-x-0 transition-transform" id="sidebar">
    <div class="px-6 py-5 flex items-center gap-3 border-b border-white/10">
      <div class="w-9 h-9 rounded-lg bg-brand-500 flex items-center justify-center font-extrabold">Rp</div>
      <span class="text-lg font-bold tracking-tight">Keuangan Panel</span>
    </div>
    <nav class="flex-1 px-3 py-4 space-y-1">
      <?php foreach ($menuItems as $item): ?>
        <?php if (in_array($user['role'], $item['roles'], true)): ?>
          <a href="<?= $item['url'] ?>"
             class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                    <?= $activePage === $item['key'] ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>">
            <?= e($item['label']) ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="p-4 border-t border-white/10">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-8 h-8 rounded-full bg-amber-400 text-brand-900 flex items-center justify-center text-sm font-bold">
          <?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold truncate"><?= e($user['name']) ?></p>
          <p class="text-xs text-white/60"><?= e(roleLabel($user['role'])) ?></p>
        </div>
      </div>
      <a href="logout.php" class="block text-center text-sm font-medium bg-white/10 hover:bg-white/15 rounded-lg py-2 transition">Keluar</a>
    </div>
  </aside>

  <div id="overlay" class="fixed inset-0 bg-black/40 z-20 hidden lg:hidden"></div>

  <!-- Main -->
  <div class="flex-1 lg:ml-64">
    <header class="sticky top-0 z-10 bg-[#F6F7F5]/90 backdrop-blur border-b border-gray-200 px-5 py-4 flex items-center gap-3">
      <button id="menuBtn" class="lg:hidden p-2 rounded-lg border border-gray-300">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <h1 class="text-xl font-bold text-ink"><?= e($pageTitle ?? '') ?></h1>
    </header>

    <main class="p-5 max-w-5xl">
      <?php if ($flash): ?>
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium border
                    <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-brand-50 text-brand-700 border-brand-200' ?>">
          <?= e($flash['message']) ?>
        </div>
      <?php endif; ?>
