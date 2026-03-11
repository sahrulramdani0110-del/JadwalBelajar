# 🛠 Panduan Instalasi Backend — Laravel JWT API

## Prasyarat
- PHP >= 8.1, Composer, MySQL/MariaDB (Laragon/XAMPP)

---

## Langkah Instalasi

```bash
# 1. Buat proyek Laravel baru
composer create-project laravel/laravel kampus-app
cd kampus-app

# 2. Install package JWT
composer require php-open-source-saver/jwt-auth

# 3. Publish config JWT
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"

# 4. Generate JWT secret key
php artisan jwt:secret
```

## Copy File-file Ini ke Proyek Laravel

```
app/Http/Controllers/Api/AuthController.php   → app/Http/Controllers/Api/
app/Http/Controllers/Api/JadwalController.php → app/Http/Controllers/Api/
app/Http/Controllers/Api/TugasController.php  → app/Http/Controllers/Api/
app/Models/User.php       → app/Models/  (GANTI yang ada)
app/Models/Jadwal.php     → app/Models/
app/Models/Tugas.php      → app/Models/
database/migrations/*.php → database/migrations/
routes/api.php            → routes/  (GANTI yang ada)
config/auth.php           → config/  (GANTI yang ada)
```

## Konfigurasi .env

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```
DB_DATABASE=kampus
DB_USERNAME=root
DB_PASSWORD=
```

## Buat Database & Migrasi

```sql
-- Di MySQL/phpMyAdmin
CREATE DATABASE kampus;
```

```bash
php artisan migrate
```

## Jalankan Server

```bash
php artisan serve
# API siap di: http://localhost:8000/api
```

---

## Uji dengan cURL

### Register
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Budi","username":"budi","email":"budi@email.com","password":"123456","password_confirmation":"123456"}'
```

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"budi@email.com","password":"123456"}'
```
→ Simpan `access_token` dari response

### Akses Endpoint (gunakan token)
```bash
curl http://localhost:8000/api/jadwal \
  -H "Authorization: Bearer {access_token}"
```

### Refresh Token
```bash
curl -X POST http://localhost:8000/api/auth/refresh \
  -H "Authorization: Bearer {access_token}"
```

### Logout
```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer {access_token}"
```

---

## Format Response API

### ✅ Success
```json
{ "status": true, "message": "...", "data": { } }
```

### ❌ Validasi Error (422)
```json
{ "status": false, "message": "Validasi gagal", "errors": { "field": ["pesan"] } }
```

### ❌ Unauthorized (401)
```json
{ "status": false, "message": "Email atau password salah" }
```
