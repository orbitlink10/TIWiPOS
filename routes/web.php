<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PostController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', LogoutController::class)->name('logout');
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
// Password reset
Route::get('/password/forgot', [ForgotPasswordController::class, 'show'])->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class, 'send'])->name('password.email');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
Route::post('/password/reset', [ResetPasswordController::class, 'store'])->name('password.update');

// Authenticated-only pages
Route::middleware('auth')->group(function () {
    Route::get('/content', [PostController::class, 'index'])->name('content.index');
    Route::get('/content/create', [PostController::class, 'create'])->name('content.create');
    Route::post('/content', [PostController::class, 'store'])->name('content.store');
    Route::get('/content/{post:slug}', [PostController::class, 'show'])->name('content.show');

    Route::get('/stock', [PageController::class, 'stock'])->name('stock');
    Route::get('/sale', [PageController::class, 'sale'])->name('sale');
    Route::post('/sale', [SaleController::class, 'store'])->name('sale.store');
    Route::get('/sale/receipt/{sale}', [SaleController::class, 'receipt'])->name('sale.receipt');

    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');

    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');

    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');

    Route::get('/stock/adjust', [StockController::class, 'adjustForm'])->name('stock.adjust');
    Route::post('/stock/adjust', [StockController::class, 'adjustStore'])->name('stock.adjust.store');

    Route::get('/summary', [PageController::class, 'summary'])->name('summary');
});
