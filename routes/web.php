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
    Route::get('/operator/school', [SchoolController::class, 'index'])->name('operator.school');
    Route::post('/operator/school', [SchoolController::class, 'store'])->name('operator.school.store');
    Route::patch('/operator/school/{school}', [SchoolController::class, 'update'])->name('operator.school.update');
    Route::delete('/operator/school/{school}', [SchoolController::class, 'destroy'])->name('operator.school.destroy');

    // Manage students
    Route::get('/operator/student', [StudentController::class, 'index'])->name('operator.student');
    Route::post('/operator/student', [StudentController::class, 'store'])->name('operator.student.store');
    Route::patch('/operator/student/{student}', [StudentController::class, 'update'])->name('operator.student.update');
    Route::delete('/operator/student/{student}', [StudentController::class, 'destroy'])->name('operator.student.destroy');
    Route::get('/operator/student/{student}/qr-code', [StudentController::class, 'qrCode'])->name('operator.student.qr-code');

    // Manage menu category
    Route::get('/operator/menuCategory', [MenuCategoryController::class, 'index'])->name('operator.menuCategory');
    Route::post('/operator/menuCategory', [MenuCategoryController::class, 'store'])->name('operator.menuCategory.store');
    Route::patch('/operator/menuCategory/{menuCategory}', [MenuCategoryController::class, 'update'])->name('operator.menuCategory.update');
    Route::delete('/operator/menuCategory/{menuCategory}', [MenuCategoryController::class, 'destroy'])->name('operator.menuCategory.destroy');

    // Manage menu
    Route::get('/operator/menu', [MenuController::class, 'index'])->name('operator.menu');
    Route::post('/operator/menu', [MenuController::class, 'store'])->name('operator.menu.store');
    Route::patch('/operator/menu/{menu}', [MenuController::class, 'update'])->name('operator.menu.update');
    Route::delete('/operator/menu/{menu}', [MenuController::class, 'destroy'])->name('operator.menu.destroy');
});

require __DIR__.'/auth.php';
