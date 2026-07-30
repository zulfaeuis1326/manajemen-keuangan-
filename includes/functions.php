<?php

function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function redirectWith(string $url, string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: ' . $url);
    exit;
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function monthNameId(int $month): string
{
    $names = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return $names[$month - 1] ?? '';
}
