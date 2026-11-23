<?php

use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\CatalogController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

Route::get('/', function (Request $request): View|RedirectResponse {
    if ($request->user() !== null) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', function (Request $request): View|RedirectResponse {
    if ($request->user()?->isAdmin()) {
        return redirect()->route('admin.assets.index');
    }

    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/{asset}', [CatalogController::class, 'show'])->name('catalog.show');
    Route::post('/catalog/{asset}/borrowings', [BorrowingController::class, 'store'])->name('borrowings.store');

    Route::get('/my-borrowings', [BorrowingController::class, 'index'])->name('my-borrowings.index');
    Route::get('/my-borrowings/{borrowing}/receipt', [BorrowingController::class, 'receipt'])->name('my-borrowings.receipt');
});

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('assets', AssetController::class)->except(['show']);
    });
