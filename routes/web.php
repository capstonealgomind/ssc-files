<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/elections', [PageController::class, 'elections'])->name('elections');
    Route::get('/candidates', [PageController::class, 'candidates'])->name('candidates');
    Route::get('/voters', [PageController::class, 'voters'])->name('voters');
    Route::get('/monitoring', [PageController::class, 'monitoring'])->name('monitoring');
    Route::get('/reports', [PageController::class, 'reports'])->name('reports');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings');
    Route::get('/accounts', [AccountController::class, 'index'])->middleware('admin')->name('accounts');
    Route::post('/accounts', [AccountController::class, 'store'])->middleware('admin')->name('accounts.store');
    Route::put('/accounts/{user}', [AccountController::class, 'update'])->middleware('admin')->name('accounts.update');
    Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->middleware('admin')->name('accounts.destroy');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
