<?php

use Illuminate\Support\Facades\Route;

// Override Fortify: /login and /register serve the customer SPA
Route::get('/login',    fn () => view('customer'))->name('login');
Route::get('/register', fn () => view('customer'));

// Driver SPA
Route::get('/driver/{any?}', fn () => view('driver'))
    ->where('any', '.*')
    ->name('driver.spa');

// Operator SPA
Route::get('/operator/{any?}', fn () => view('operator'))
    ->where('any', '.*')
    ->name('operator.spa');

// Admin SPA
Route::get('/admin/{any?}', fn () => view('admin'))
    ->where('any', '.*')
    ->name('admin.spa');

require __DIR__.'/settings.php';

// Named stubs cho Wayfinder (Inertia scaffolding vẫn tham chiếu các tên này)
Route::get('/',          fn () => view('customer'))->name('home');
Route::get('/dashboard', fn () => view('customer'))->name('dashboard');

// Customer SPA — catch-all (phải đặt CUỐI CÙNG, loại trừ /api/ để tránh chặn API routes)
Route::get('/{any?}', fn () => view('customer'))
    ->where('any', '^(?!api/).*')
    ->name('customer.spa');
