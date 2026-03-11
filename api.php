<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JadwalController;
use App\Http\Controllers\Api\TugasController;
use Illuminate\Support\Facades\Route;

/*
|──────────────────────────────────────────────────────────────
| API Routes  —  routes/api.php
|──────────────────────────────────────────────────────────────
| Base URL: http://localhost:8000/api
|
| METHOD | ENDPOINT              | KETERANGAN            | JWT
|--------|----------------------|-----------------------|----
| POST   | /auth/register       | Daftar akun baru      | ✗
| POST   | /auth/login          | Login, dapat token    | ✗
| POST   | /auth/logout         | Invalidate token      | ✓
| POST   | /auth/refresh        | Refresh JWT token     | ✓
| GET    | /auth/me             | Data user login       | ✓
| GET    | /jadwal              | List semua jadwal     | ✓
| POST   | /jadwal              | Tambah jadwal         | ✓
| GET    | /jadwal/{id}         | Detail jadwal         | ✓
| PUT    | /jadwal/{id}         | Update jadwal         | ✓
| DELETE | /jadwal/{id}         | Hapus jadwal          | ✓
| GET    | /tugas               | List semua tugas      | ✓
| POST   | /tugas               | Tambah tugas          | ✓
| GET    | /tugas/{id}          | Detail tugas          | ✓
| PUT    | /tugas/{id}          | Update tugas          | ✓
| DELETE | /tugas/{id}          | Hapus tugas           | ✓
|──────────────────────────────────────────────────────────────
*/

// ── Public Routes (tanpa JWT) ──────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ── Protected Routes (wajib JWT Bearer Token) ─────────────
Route::middleware('auth:api')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me',       [AuthController::class, 'me']);
    });

    Route::apiResource('jadwal', JadwalController::class);
    Route::apiResource('tugas',  TugasController::class);
});
