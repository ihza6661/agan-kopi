<h1>Point of Sale Toko Sembako</h1>

Sistem kasir (POS) untuk toko sembako berbasis Laravel 12. Mendukung manajemen produk & kategori, transaksi kasir, laporan, pengaturan toko, notifikasi stok, serta integrasi pembayaran Midtrans.

## ✨ Fitur

- Autentikasi & peran: admin, kasir
- Manajemen kategori & produk (datatables)
- Kasir: scan/cari produk, hold/resume transaksi, cetak struk dengan format kustom
- Pembayaran: integrasi Midtrans (Snap), webhook notifikasi
- Laporan penjualan + unduh
- Pengaturan toko (nama, alamat, telepon, mata uang, pajak, diskon, format struk, logo)
- Notifikasi stok menipis & produk mendekati kadaluarsa
- Log aktivitas pengguna

## 🧰 Teknologi

- PHP ^8.2, Laravel ^12, Inertia.js, React 19
- PostgreSQL (Production), MySQL/SQLite (Local)
- Yajra DataTables (server-side)
- Midtrans PHP SDK
- Vite, Tailwind CSS 4

## 🌿 Branch Strategy

Repository ini menggunakan 2 branch utama:

- **`production`** - Kode yang sedang running di production (Heroku)
  - URL: https://agan-kopi-pos-b332db5d7f2e.herokuapp.com/
  - Stabil, sudah di-test
  - Hanya update dari `main` setelah testing
  
- **`main`** - Development branch (default)
  - Development aktif terjadi di sini
  - Fitur baru, bug fixes, eksperimen
  - Setelah stabil, merge ke `production`

### Workflow:
```bash
# Development
git checkout main
git pull origin main
# ... kerjakan fitur baru ...
git commit -m "feat: fitur baru"
git push origin main

# Deploy ke Production
git checkout production
git merge main
git push origin production
git push heroku production:main  # Deploy ke Heroku
```

## ✅ Prasyarat

- PHP 8.2+
- Database Relational (SQLLite, MySQL atau PostgreSQL)

## 🚀 Install Project

1) Clone repo
```powershell
git clone https://github.com/WageFolabessy/point_of_sale_minimarket.git
```

2) Masuk folder proyek
```powershell
cd point_of_sale_minimarket
```

3) Install Dependency
```powershell
composer install
```

4) Salin env dan generate app key
```powershell
Copy-Item .env.example .env
php artisan key:generate
```

5) Setup database dan sesuaikan dengan konfigurasi database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos
DB_USERNAME=root
DB_PASSWORD=secret
```

6) Jalankan migrasi dan seeder
```powershell
php artisan migrate --seed
```

7) Jalankan server Laravel

```powershell
php artisan serve
```

## 🔐 Kredensial

- Admin: admin@admin.com / password
- Kasir: kasir@kasir.com / password


## 💳 Midtrans

Isi variabel berikut di `.env`:

```env
MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_CLIENT_KEY=your_client_key
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true

# Opsional: override notification URL (contoh pakai ngrok http 8000)
NGROK_HTTP_8000=https://<your-ngrok-subdomain>.ngrok-free.app/midtrans/notification
```

Webhook (POST) akan menerima notifikasi di:

- `POST /midtrans/notification`

Jika lokal, jalankan tunneling (ngrok) dan set `NGROK_HTTP_8000` agar Midtrans bisa memanggil endpoint lokal.

## ⚙️ Pengaturan Aplikasi

Halaman Pengaturan memungkinkan ubah:
- Nama/Alamat/Telepon/Logo Toko
- Mata uang, Pajak (%), Diskon (%)
- Format Nomor Struk (mis: `INV-{YYYY}{MM}{DD}-{SEQ:6}`)

## 🧪 Menjalankan Test

```powershell
php artisan test
# atau
vendor\bin\phpunit --testsuite Unit,Feature
```

## 📄 Lisensi

MIT
