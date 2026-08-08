<?php

use App\Http\Controllers\ChildrenController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\StudentController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/stripe/webhook', [StripePaymentController::class, 'webhook'])->name('stripe.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Manage a customer's children
    Route::get('/customer/children', [ChildrenController::class, 'index'])->name('customer.children');
    Route::post('/customer/children', [ChildrenController::class, 'store'])->name('customer.children.store');
    Route::patch('/customer/children/{child}', [ChildrenController::class, 'update'])->name('customer.children.update');
    Route::delete('/customer/children/{child}', [ChildrenController::class, 'destroy'])->name('customer.children.destroy');

    // Customer ordering
    Route::get('/customer/menu', [CustomerOrderController::class, 'menu'])->name('customer.menu');
    Route::post('/customer/cart', [CustomerOrderController::class, 'addToCart'])->name('customer.cart.store');
    Route::put('/customer/cart-selection', [CustomerOrderController::class, 'setCartQuantity'])->name('customer.cart.selection');
    Route::patch('/customer/cart/{cartItem}', [CustomerOrderController::class, 'updateCart'])->name('customer.cart.update');
    Route::delete('/customer/cart/{cartItem}', [CustomerOrderController::class, 'removeFromCart'])->name('customer.cart.destroy');
    Route::post('/customer/checkout', [StripePaymentController::class, 'checkout'])->name('customer.checkout');
    Route::get('/customer/checkout/success', [StripePaymentController::class, 'success'])->name('customer.checkout.success');
    Route::get('/customer/checkout/cancel/{order}', [StripePaymentController::class, 'cancel'])->name('customer.checkout.cancel');
    Route::get('/customer/orders', [CustomerOrderController::class, 'orders'])->name('customer.orders');
    Route::patch('/customer/orders/items/{orderItem}', [CustomerOrderController::class, 'updateOrderedMeal'])->name('customer.orders.items.update');

    // Manage schools
    Route::get('/owner/school', [SchoolController::class, 'index'])->name('owner.school');
    Route::post('/owner/school', [SchoolController::class, 'store'])->name('owner.school.store');
    Route::patch('/owner/school/{school}', [SchoolController::class, 'update'])->name('owner.school.update');
    Route::delete('/owner/school/{school}', [SchoolController::class, 'destroy'])->name('owner.school.destroy');

    // Manage students
    Route::get('/owner/student', [StudentController::class, 'index'])->name('owner.student');
    Route::post('/owner/student', [StudentController::class, 'store'])->name('owner.student.store');
    Route::patch('/owner/student/{student}', [StudentController::class, 'update'])->name('owner.student.update');
    Route::delete('/owner/student/{student}', [StudentController::class, 'destroy'])->name('owner.student.destroy');
    Route::get('/owner/student/{student}/qr-code', [StudentController::class, 'qrCode'])->name('owner.student.qr-code');

    // Manage menu category
    Route::get('/owner/menuCategory', [MenuCategoryController::class, 'index'])->name('owner.menuCategory');
    Route::post('/owner/menuCategory', [MenuCategoryController::class, 'store'])->name('owner.menuCategory.store');
    Route::patch('/owner/menuCategory/{menuCategory}', [MenuCategoryController::class, 'update'])->name('owner.menuCategory.update');
    Route::delete('/owner/menuCategory/{menuCategory}', [MenuCategoryController::class, 'destroy'])->name('owner.menuCategory.destroy');

    // Manage menu
    Route::get('/owner/menu', [MenuController::class, 'index'])->name('owner.menu');
    Route::post('/owner/menu', [MenuController::class, 'store'])->name('owner.menu.store');
    Route::patch('/owner/menu/{menu}', [MenuController::class, 'update'])->name('owner.menu.update');
    Route::delete('/owner/menu/{menu}', [MenuController::class, 'destroy'])->name('owner.menu.destroy');
});

require __DIR__.'/auth.php';
