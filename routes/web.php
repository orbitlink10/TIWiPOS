<?php

use Illuminate\Support\Facades\Route;
use App\Models\Post;
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
use App\Http\Controllers\BranchController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Admin\TenantController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::match(['get', 'post'], '/logout', LogoutController::class)->name('logout');
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');
Route::get('/blog', [PostController::class, 'blogIndex'])->name('blog.index');
Route::get('/post-image/{post}', [PostController::class, 'image'])->name('post.image');
Route::get('/sitemap.xml', [PostController::class, 'sitemap'])->name('sitemap');
// Password reset
Route::get('/password/forgot', [ForgotPasswordController::class, 'show'])->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class, 'send'])->name('password.email');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
Route::post('/password/reset', [ResetPasswordController::class, 'store'])->name('password.update');

// Authenticated-only pages
Route::middleware(['auth', 'subscription.gate'])->group(function () {
    Route::get('/content', [PostController::class, 'index'])->name('content.index');
    Route::get('/content/create', [PostController::class, 'create'])->name('content.create');
    Route::post('/content', [PostController::class, 'store'])->name('content.store');
    Route::post('/content/bulk-action', [PostController::class, 'bulkAction'])->name('content.bulk');
    Route::get('/content/{post}/edit', [PostController::class, 'edit'])->name('content.edit');
    Route::put('/content/{post}', [PostController::class, 'update'])->name('content.update');
    Route::delete('/content/{post}', [PostController::class, 'destroy'])->name('content.destroy');

    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/pay', [BillingController::class, 'pay'])->name('billing.pay');
    Route::post('/payments/record', [PaymentController::class, 'store'])->name('payments.store');

    Route::get('/stock', [PageController::class, 'stock'])->name('stock');
    Route::get('/sale', [PageController::class, 'sale'])->name('sale');
    Route::post('/sale', [SaleController::class, 'store'])->name('sale.store');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}/edit', [SaleController::class, 'edit'])->middleware('role.ability:edit_sales')->name('sales.edit');
    Route::put('/sales/{sale}', [SaleController::class, 'update'])->middleware('role.ability:edit_sales')->name('sales.update');
    Route::get('/sale/receipt/{sale}', [SaleController::class, 'receipt'])->name('sale.receipt');

    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/create', [ProductController::class, 'create'])->middleware('role.ability:manage_catalog')->name('products.create');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->middleware('role.ability:manage_catalog')->name('products.edit');
    Route::post('/products', [ProductController::class, 'store'])->middleware('role.ability:manage_catalog')->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('role.ability:manage_catalog')->name('products.update');
    Route::patch('/products/{product}/status', [ProductController::class, 'status'])->middleware('role.ability:manage_catalog')->name('products.status');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('role.ability:manage_catalog')->name('products.destroy');

    Route::get('/categories/create', [CategoryController::class, 'create'])->middleware('role.ability:manage_catalog')->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role.ability:manage_catalog')->name('categories.store');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role.ability:manage_catalog')->name('categories.destroy');

    Route::get('/suppliers/create', [SupplierController::class, 'create'])->middleware('role.ability:manage_catalog')->name('suppliers.create');
    Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('role.ability:manage_catalog')->name('suppliers.store');

    Route::get('/stock/adjust', [StockController::class, 'adjustForm'])->middleware('role.ability:adjust_stock')->name('stock.adjust');
    Route::post('/stock/adjust', [StockController::class, 'adjustStore'])->middleware('role.ability:adjust_stock')->name('stock.adjust.store');
    Route::get('/stock/{product}/edit', [StockController::class, 'edit'])->whereNumber('product')->middleware('role.ability:adjust_stock')->name('stock.edit');
    Route::put('/stock/{product}', [StockController::class, 'update'])->whereNumber('product')->middleware('role.ability:adjust_stock')->name('stock.update');

    Route::get('/summary', [PageController::class, 'summary'])->name('summary');

    Route::get('/staff', [StaffController::class, 'index'])->middleware('role.ability:manage_staff')->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->middleware('role.ability:manage_staff')->name('staff.store');
    Route::patch('/staff/{user}/status', [StaffController::class, 'status'])->middleware('role.ability:manage_staff')->name('staff.status');
    Route::patch('/staff/{user}/role', [StaffController::class, 'role'])->middleware('role.ability:manage_staff')->name('staff.role');
    Route::patch('/staff/{user}/branch', [StaffController::class, 'branch'])->middleware('role.ability:manage_staff')->name('staff.branch');

    Route::get('/settings', [SettingsController::class, 'index'])->middleware('role.ability:manage_settings')->name('settings.index');

    // Branch management
    Route::get('/branches', [BranchController::class, 'index'])->middleware('role.ability:manage_branches')->name('branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->middleware('role.ability:manage_branches')->name('branches.store');
    Route::post('/branches/switch', [BranchController::class, 'switch'])->name('branches.switch');
});

// Legacy content URL support: /content/{slug} -> /{slug}
Route::get('/content/{post:slug}', function (Post $post) {
    return redirect()->route('post.show', ['post' => $post->slug], 301);
})->name('content.show');

Route::middleware(['auth', 'super.admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::post('/tenants/{business}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
    Route::post('/tenants/{business}/deactivate', [TenantController::class, 'deactivate'])->name('tenants.deactivate');
    Route::post('/tenants/{business}/impersonate', [TenantController::class, 'impersonate'])->name('tenants.impersonate');
});

// Public content URL: https://tiwi.co.ke/{page-title}
Route::get('/{post:slug}', [PostController::class, 'show'])->name('post.show');
