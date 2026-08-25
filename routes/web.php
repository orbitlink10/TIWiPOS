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
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceVisitController;
use App\Http\Controllers\ServiceWorkerController;
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
Route::get('/site', [HomeController::class, 'landing'])->name('site.home');
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
    Route::get('/content', [PostController::class, 'index'])->middleware('super.admin')->name('content.index');
    Route::get('/content/create', [PostController::class, 'create'])->middleware('super.admin')->name('content.create');
    Route::post('/content', [PostController::class, 'store'])->middleware('super.admin')->name('content.store');
    Route::post('/content/bulk-action', [PostController::class, 'bulkAction'])->middleware('super.admin')->name('content.bulk');
    Route::get('/content/{post}/edit', [PostController::class, 'edit'])->middleware('super.admin')->name('content.edit');
    Route::put('/content/{post}', [PostController::class, 'update'])->middleware('super.admin')->name('content.update');
    Route::delete('/content/{post}', [PostController::class, 'destroy'])->middleware('super.admin')->name('content.destroy');

    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/pay', [BillingController::class, 'pay'])->name('billing.pay');
    Route::post('/payments/record', [PaymentController::class, 'store'])->name('payments.store');

    Route::get('/stock', [PageController::class, 'stock'])->middleware('role.ability:view_stock')->name('stock');
    Route::get('/sale', [PageController::class, 'sale'])->name('sale');
    Route::post('/sale', [SaleController::class, 'store'])->name('sale.store');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}/edit', [SaleController::class, 'edit'])->middleware('role.ability:edit_sales')->name('sales.edit');
    Route::put('/sales/{sale}', [SaleController::class, 'update'])->middleware('role.ability:edit_sales')->name('sales.update');
    Route::get('/sale/receipt/{sale}', [SaleController::class, 'receipt'])->name('sale.receipt');

    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::redirect('/services', '/delivery');
    Route::get('/delivery', [ServiceController::class, 'index'])->name('services');
    Route::get('/products/create', [ProductController::class, 'create'])->middleware('role.ability:manage_catalog')->name('products.create');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->middleware('role.ability:manage_catalog')->name('products.edit');
    Route::post('/products', [ProductController::class, 'store'])->middleware('role.ability:manage_catalog')->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('role.ability:manage_catalog')->name('products.update');
    Route::patch('/products/{product}/status', [ProductController::class, 'status'])->middleware('role.ability:manage_catalog')->name('products.status');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('role.ability:delete_products')->name('products.destroy');
    Route::get('/delivery/create', [ServiceController::class, 'create'])->middleware('role.ability:manage_catalog')->name('services.create');
    Route::get('/delivery/{service}/edit', [ServiceController::class, 'edit'])->middleware('role.ability:manage_catalog')->name('services.edit');
    Route::post('/delivery', [ServiceController::class, 'store'])->middleware('role.ability:manage_catalog')->name('services.store');
    Route::put('/delivery/{service}', [ServiceController::class, 'update'])->middleware('role.ability:manage_catalog')->name('services.update');
    Route::delete('/delivery/{service}', [ServiceController::class, 'destroy'])->middleware('role.ability:manage_catalog')->name('services.destroy');
    Route::get('/delivery-categories/create', [ServiceCategoryController::class, 'create'])->middleware('role.ability:manage_catalog')->name('service-categories.create');
    Route::post('/delivery-categories', [ServiceCategoryController::class, 'store'])->middleware('role.ability:manage_catalog')->name('service-categories.store');
    Route::delete('/delivery-categories/{serviceCategory}', [ServiceCategoryController::class, 'destroy'])->middleware('role.ability:manage_catalog')->name('service-categories.destroy');
    Route::post('/delivery-persons', [ServiceWorkerController::class, 'store'])->middleware('role.ability:manage_catalog')->name('service-workers.store');
    Route::get('/delivery-persons/{serviceWorker}/edit', [ServiceWorkerController::class, 'edit'])->middleware('role.ability:manage_catalog')->name('service-workers.edit');
    Route::put('/delivery-persons/{serviceWorker}', [ServiceWorkerController::class, 'update'])->middleware('role.ability:manage_catalog')->name('service-workers.update');
    Route::patch('/delivery-persons/{serviceWorker}/status', [ServiceWorkerController::class, 'status'])->middleware('role.ability:manage_catalog')->name('service-workers.status');
    Route::post('/deliveries', [ServiceVisitController::class, 'store'])->middleware('role.ability:manage_catalog')->name('service-visits.store');
    Route::get('/deliveries/{serviceVisit}/edit', [ServiceVisitController::class, 'edit'])->middleware('role.ability:manage_catalog')->name('service-visits.edit');
    Route::put('/deliveries/{serviceVisit}', [ServiceVisitController::class, 'update'])->middleware('role.ability:manage_catalog')->name('service-visits.update');
    Route::patch('/deliveries/{serviceVisit}/status', [ServiceVisitController::class, 'status'])->middleware('role.ability:manage_catalog')->name('service-visits.status');

    Route::get('/categories/create', [CategoryController::class, 'create'])->middleware('role.ability:manage_catalog')->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role.ability:manage_catalog')->name('categories.store');
    Route::get('/sub-categories/create', [CategoryController::class, 'createSubCategory'])->middleware('role.ability:manage_catalog')->name('sub-categories.create');
    Route::post('/sub-categories', [CategoryController::class, 'storeSubCategory'])->middleware('role.ability:manage_catalog')->name('sub-categories.store');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role.ability:delete_products')->name('categories.destroy');

    Route::get('/suppliers/create', [SupplierController::class, 'create'])->middleware('role.ability:manage_catalog')->name('suppliers.create');
    Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('role.ability:manage_catalog')->name('suppliers.store');

    Route::get('/stock/adjust', [StockController::class, 'adjustForm'])->middleware('role.ability:add_stock')->name('stock.adjust');
    Route::post('/stock/adjust', [StockController::class, 'adjustStore'])->middleware('role.ability:add_stock')->name('stock.adjust.store');
    Route::get('/stock/{product}/edit', [StockController::class, 'edit'])->whereNumber('product')->middleware('role.ability:add_stock')->name('stock.edit');
    Route::put('/stock/{product}', [StockController::class, 'update'])->whereNumber('product')->middleware('role.ability:add_stock')->name('stock.update');

    Route::get('/summary', [PageController::class, 'summary'])->name('summary');

    Route::get('/staff', [StaffController::class, 'index'])->middleware('role.ability:manage_staff')->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->middleware('role.ability:manage_staff')->name('staff.store');
    Route::patch('/staff/{user}/status', [StaffController::class, 'status'])->middleware('role.ability:manage_staff')->name('staff.status');
    Route::patch('/staff/{user}/role', [StaffController::class, 'role'])->middleware('role.ability:manage_staff')->name('staff.role');
    Route::patch('/staff/{user}/branch', [StaffController::class, 'branch'])->middleware('role.ability:manage_staff')->name('staff.branch');

    Route::get('/settings', [SettingsController::class, 'index'])->middleware('role.ability:manage_profile')->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->middleware('role.ability:manage_profile')->name('settings.profile.update');
    Route::patch('/settings/password', [SettingsController::class, 'updatePassword'])->middleware('role.ability:manage_profile')->name('settings.password.update');

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
    Route::patch('/users/{user}/phone', [TenantController::class, 'updateUserPhone'])->name('users.phone');
    Route::patch('/users/{user}/status', [TenantController::class, 'status'])->name('users.status');
    Route::delete('/users/{user}', [TenantController::class, 'destroyUser'])->name('users.destroy');
});

// Public content URL: https://tiwi.co.ke/{page-title}
Route::get('/{post:slug}', [PostController::class, 'show'])->name('post.show');
