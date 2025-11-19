<?php

use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\TransactionController;
use App\Http\Controllers\Api\Member\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\BookController as AdminBookController;
use App\Http\Controllers\Api\Admin\MemberController;
// ... (Import Controller lainnya)

// 1. ROUTE OTENTIKASI (Public)
Route::post('login', [AuthController::class, 'login']);

// 2. ROUTE UNTUK ANGGOTA & ADMIN (Membutuhkan Login)
Route::middleware('auth:sanctum')->group(function () {
Route::post('logout', [AuthController::class, 'logout']);

// Pencarian Buku untuk Anggota & Admin
Route::get('books', [AdminBookController::class, 'index']);
Route::get('books/{id}', [AdminBookController::class, 'show']);

// ROUTE KHUSUS ANGGOTA
Route::prefix('member')->middleware('role:anggota')->group(function () {
// Profile Anggota
Route::get('profile', [ProfileController::class, 'show']);
Route::put('profile', [ProfileController::class, 'update']);
Route::put('profile/password', [ProfileController::class, 'changePassword']);

// Riwayat Peminjaman Anggota
Route::get('borrowings', [ProfileController::class, 'myBorrowings']);
});

// ROUTE KHUSUS ADMIN
Route::prefix('admin')->middleware('role:admin')->group(function () {

// MANAJEMEN BUKU & KATEGORI
Route::apiResource('books', AdminBookController::class)->except(['index', 'show']);
Route::apiResource('categories', CategoryController::class);

// MANAJEMEN ANGGOTA
Route::apiResource('members', MemberController::class);
Route::post('members/{id}/status', [MemberController::class, 'updateStatus']); // Update Status Aktif/Non-Aktif

// TRANSAKSI
Route::post('transactions/borrow', [TransactionController::class, 'borrowBook']);
Route::post('transactions/return/{borrowing}', [TransactionController::class, 'returnBook']);
Route::post('transactions/extend/{borrowing}', [TransactionController::class, 'extendBorrowing']);

// DENDA & KEUANGAN
Route::get('fines', [TransactionController::class, 'listFines']);
Route::post('fines/{fine}/pay', [TransactionController::class, 'payFine']);

// LAPORAN
Route::get('reports/borrowings', [ReportController::class, 'borrowingReport']);
Route::get('reports/top-books', [ReportController::class, 'topBooksReport']);
Route::get('reports/financial', [ReportController::class, 'financialReport']);
});
});
