# Keuangan Panel

Web manajemen keuangan dengan sistem login 3 level (Superadmin, Admin, Pengguna),
kategori, grafik, dan laporan bulanan.

Dibangun pakai PHP native + **SQLite** (bukan MySQL) supaya:
- Tidak perlu service database terpisah
- Tidak perlu install/config driver database (penyebab error "could not find driver" di Railway)
- Tabel & data awal dibuat **otomatis** saat aplikasi pertama kali jalan — tidak perlu migrasi manual

## Login awal

- Username: `admin`
- Kata sandi: `admin123`

**Segera ganti/hapus akun ini atau buat superadmin baru lalu hapus yang lama setelah deploy.**

## Level pengguna

| Peran | Transaksi | Kategori | Kelola Pengguna |
|---|---|---|---|
| Superadmin | Semua data | Kelola | Kelola |
| Admin | Semua data | Kelola | - |
| Pengguna | Data sendiri saja | Lihat saja | - |

## Deploy ke Railway

1. Push folder ini ke repo GitHub, lalu hubungkan repo tersebut ke project Railway (New Project → Deploy from GitHub repo).
2. Railway (Railpack) akan otomatis mendeteksi ini sebagai project PHP — **tidak perlu** `nixpacks.toml` atau environment variable driver database apapun, karena SQLite sudah termasuk secara default di PHP.
3. **Penting — supaya data tidak hilang saat redeploy:**
   Buka tab **Settings** pada service ini di Railway → bagian **Volumes** → klik **New Volume**.
   Isi **Mount Path** dengan:
   ```
   /app/storage
   ```
   Ini membuat file `database.sqlite` tersimpan permanen, tidak ikut terhapus tiap kali ada deploy baru.
4. Deploy. Setelah selesai, buka domain yang diberikan Railway — otomatis diarahkan ke halaman login.

Tidak perlu setting environment variable apapun untuk database. Aplikasi ini sengaja dibuat sesedikit mungkin bagian yang bisa gagal saat deploy.

## Menjalankan di komputer sendiri (opsional, buat testing)

Butuh PHP 8.1+ terpasang di komputer.

```bash
php -S localhost:8000
```

Lalu buka `http://localhost:8000` di browser.

## Struktur folder

```
config/database.php     -> koneksi SQLite + auto-migrasi tabel + seed data awal
includes/auth.php        -> login, session, cek peran
includes/functions.php   -> fungsi bantuan (format rupiah, dll)
includes/header.php      -> sidebar + topbar
includes/footer.php      -> penutup layout
storage/database.sqlite  -> file database (dibuat otomatis, jangan dihapus manual)
*.php (di root)          -> halaman-halaman aplikasi
```
