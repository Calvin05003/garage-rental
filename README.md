# Panduan Instalasi Garage Rental

Siapkan PHP 8.3+ (seri PHP 8) dengan ekstensi `pdo_mysql` dan `fileinfo`, Composer 2, MySQL, serta Node.js 22.12+ dan npm.

## 1. Unduh proyek dan pasang dependensi

```sh
git clone https://github.com/Calvin05003/garage-rental.git
cd garage-rental
composer install
npm ci
```

## 2. Buat file konfigurasi

Salin `.env.example` menjadi `.env`:

```sh
cp .env.example .env
```

Pada Windows PowerShell, gunakan `Copy-Item .env.example .env`.

```sh
php artisan key:generate
```

## 3. Siapkan database

Jalankan MySQL, lalu buat database melalui MySQL CLI atau phpMyAdmin:

```sql
CREATE DATABASE garage_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sesuaikan koneksi di `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=garage_rental
DB_USERNAME=root
DB_PASSWORD=
```

Isi `DB_USERNAME` dan `DB_PASSWORD` sesuai akun MySQL Anda.

## 4. Buat tabel dan akun admin

Jalankan pada database baru tersebut:

```sh
php artisan config:clear
php artisan migrate --seed
php artisan db:seed --class=AdminSeeder
```

## 5. Jalankan aplikasi

```sh
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Buka **http://127.0.0.1:8000** dan login menggunakan akun admin lokal:

- Email: `admin@gmail.com`
- Password: `admin123`
