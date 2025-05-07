# 🌾 Local Farmer Connect

![Local Farmer Connect](https://images.unsplash.com/photo-1523741543316-beb7fc7023d8?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80)

## 📝 Daftar Isi
- [Deskripsi Proyek](#deskripsi-proyek)
- [Struktur Proyek](#struktur-proyek)
- [Komponen Utama](#komponen-utama)
- [Tahapan Penggunaan](#tahapan-penggunaan)
- [Fitur](#fitur)
- [Peran Pengguna](#peran-pengguna)
- [Pengaturan Database](#pengaturan-database)
- [Instalasi](#instalasi)
- [Penggunaan](#penggunaan)

## 🌱 Deskripsi Proyek

Local Farmer Connect adalah platform yang menghubungkan petani lokal langsung dengan konsumen. Aplikasi ini memungkinkan petani untuk membuat toko online dan menjual produk pertanian mereka langsung ke konsumen tanpa perantara, sehingga menjamin harga yang lebih baik untuk semua pihak dan kesegaran produk.

## 📂 Struktur Proyek

```
local-farmer-connect/
├── admin/                 # Administrasi sistem
├── api/                   # Endpoint API untuk fungsi AJAX
├── auth/                  # Autentikasi (login, register, dll)
├── cart/                  # Fungsionalitas keranjang belanja
├── components/            # Komponen yang dapat digunakan kembali
├── config/                # Konfigurasi aplikasi
├── includes/              # File yang sering disertakan
├── orders/                # Manajemen pesanan
├── products/              # Katalog produk
├── public/                # Aset statis (CSS, gambar, JS)
├── reviews/               # Sistem ulasan
├── src/                   # Kode sumber untuk pengembangan
├── stores/                # Manajemen toko
└── uploads/               # Direktori upload gambar
```

## 🧩 Komponen Utama

### 1. Modul Database (`config/database.php`)

Mengelola koneksi database dan menyediakan fungsi utilitas umum seperti:
- Koneksi ke database MySQL
- Manajemen sesi pengguna
- Fungsi autentikasi dan otorisasi
- Penanganan upload gambar

### 2. Komponen UI (`components/`)

- **`product-card.php`**: Menampilkan kartu produk dengan gambar, nama, harga, dan rating
- **`review-summary.php`**: Menampilkan ringkasan ulasan untuk produk atau toko

### 3. Modul Autentikasi (`auth/`)

- Sistem login dan register
- Manajemen peran pengguna (Admin, Petani, Pelanggan)
- Reset password

### 4. Manajemen Toko (`stores/`)

- Pembuatan dan pengelolaan toko oleh petani
- Dashboard analitik toko
- Manajemen inventaris produk

### 5. Manajemen Produk (`products/`)

- Katalog produk dari semua toko
- Pencarian dan filter produk
- Detail produk dan ulasan

### 6. Keranjang Belanja (`cart/`)

- Penambahan dan penghapusan produk dari keranjang
- Perhitungan total harga
- Penyimpanan keranjang dalam sesi

### 7. Sistem Pesanan (`orders/`)

- Proses checkout
- Pelacakan status pesanan
- Riwayat pesanan

### 8. Panel Admin (`admin/`)

- Dashboard admin dengan statistik
- Manajemen pengguna
- Manajemen kategori
- Pemantauan pesanan
- Validasi toko

## 🛒 Tahapan Penggunaan

### Untuk Pelanggan:

1. **Pendaftaran/Login**
   - Daftar sebagai pelanggan
   - Login ke akun

2. **Menjelajahi Produk**
   - Lihat katalog produk
   - Filter berdasarkan kategori atau toko
   - Cari produk spesifik

3. **Melakukan Pemesanan**
   - Tambahkan produk ke keranjang
   - Lihat dan edit keranjang
   - Lakukan checkout
   - Pilih metode pembayaran

4. **Pengelolaan Pesanan**
   - Lihat status pesanan
   - Berikan ulasan untuk produk yang dibeli

### Untuk Petani:

1. **Pendaftaran/Login**
   - Daftar sebagai petani
   - Login ke akun

2. **Pengaturan Toko**
   - Buat profil toko
   - Tambahkan informasi dan gambar toko

3. **Manajemen Produk**
   - Tambah produk baru
   - Edit atau hapus produk
   - Atur harga dan stok

4. **Pengelolaan Pesanan**
   - Lihat pesanan masuk
   - Perbarui status pesanan
   - Konfirmasi pengiriman

5. **Analitik**
   - Lihat statistik penjualan
   - Pantau produk terlaris
   - Lacak pendapatan

### Untuk Admin:

1. **Login**
   - Login sebagai admin

2. **Pengelolaan Konten**
   - Kelola kategori produk
   - Verifikasi toko petani baru
   - Kelola pengguna

3. **Pemantauan**
   - Pantau pesanan
   - Lihat statistik platform
   - Tangani laporan atau masalah

## ✨ Fitur

### Fitur Utama

- **Toko Multi-Vendor**: Petani dapat membuat dan mengelola toko mereka sendiri
- **Sistem Ulasan**: Pelanggan dapat menilai dan mengulas produk atau toko
- **Sistem Keranjang Belanja**: Menambahkan, mengedit, dan checkout produk
- **Pelacakan Pesanan**: Memantau status pesanan
- **Pencarian dan Filter**: Menemukan produk berdasarkan kategori, toko, atau kata kunci
- **Notifikasi**: Pemberitahuan untuk status pesanan baru atau perubahan

### Fitur Khusus

- **Analitik Toko**: Petani dapat melihat data penjualan dan statistik toko
- **Indikator Stok Rendah**: Notifikasi saat stok produk hampir habis
- **Produk Unggulan**: Menampilkan produk terbaik di halaman beranda
- **Toko Terbaik**: Menampilkan toko dengan rating tertinggi

## 👥 Peran Pengguna

### Admin
- Mengelola seluruh platform
- Memvalidasi toko petani
- Mengelola kategori produk
- Memantau aktivitas pengguna

### Petani
- Mengelola toko
- Menambah dan memperbarui produk
- Mengelola pesanan
- Melihat analitik penjualan

### Pelanggan
- Menjelajahi dan membeli produk
- Melacak pesanan
- Memberikan ulasan
- Mengelola profil dan alamat

## 🔧 Pengaturan Database

Aplikasi menggunakan database MySQL dengan struktur berikut:

- **users**: Informasi pengguna dan autentikasi
- **stores**: Data toko petani
- **products**: Katalog produk
- **categories**: Kategori produk
- **orders**: Pesanan pelanggan
- **order_items**: Item dalam pesanan
- **cart_items**: Produk dalam keranjang
- **reviews**: Ulasan produk dan toko

## 🚀 Instalasi

1. **Prasyarat**
   - PHP 7.4 atau lebih tinggi
   - MySQL 5.7 atau lebih tinggi
   - Web server (Apache/Nginx)

2. **Langkah Instalasi**
   ```
   # Klon repositori
   git clone https://github.com/KittodGG/local-farmer-connect.git
   
   # Masuk ke direktori
   cd local-farmer-connect
   
   # Import skema database
   mysql -u username -p database_name < database/schema.sql
   
   # Konfigurasi database
   # Edit file config/database.php dengan detail database Anda
   ```

3. **Konfigurasi Web Server**
   - Arahkan document root ke direktori proyek
   - Pastikan mod_rewrite diaktifkan (untuk Apache)

## 📱 Penggunaan

1. **Akses Aplikasi**
   - Buka browser dan arahkan ke URL aplikasi
   - Register/login untuk akses penuh

2. **Contoh Penggunaan**
   - Pelanggan: Jelajahi produk, tambahkan ke keranjang, checkout
   - Petani: Kelola toko, tambahkan produk, proses pesanan
   - Admin: Kelola pengguna, validasi toko, pantau sistem

## 📝 Catatan Penting

- Pastikan direktori `uploads/` memiliki izin write yang tepat
- Konfigurasi `BASE_URL` di `config/database.php` sesuai dengan URL instalasi Anda
- Untuk keamanan, pertimbangkan untuk mengubah kredensial database default

---

© 2023 Local Farmer Connect. Dibuat dengan ❤️oleh Kitna M. F. untuk mendukung petani lokal. 
