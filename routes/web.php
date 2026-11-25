<?php

use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\BorrowingController as AdminBorrowingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

Route::get('/storage-file/{path}', function (string $path): StreamedResponse {
    abort_unless(Asset::isPublicImagePath($path), 404);

    $disk = Storage::disk('public');
    abort_unless($disk->exists($path), 404);

    $mimeType = $disk->mimeType($path);
    abort_unless(in_array($mimeType, [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/avif',
    ], true), 404);

    $publicDirectory = realpath($disk->path(''));
    $assetsDirectory = realpath($disk->path('assets'));
    $realFilePath = realpath($disk->path($path));
    abort_unless(
        $publicDirectory !== false
            && $assetsDirectory !== false
            && $realFilePath !== false
            && str_starts_with($realFilePath, $publicDirectory.DIRECTORY_SEPARATOR)
            && str_starts_with($realFilePath, $assetsDirectory.DIRECTORY_SEPARATOR)
            && is_file($realFilePath),
        404,
    );

    return $disk->response($path, basename($path), [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=86400',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->where('path', '.*')->name('storage-file.show');

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

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

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
        Route::get('/borrowings', [AdminBorrowingController::class, 'index'])->name('borrowings.index');
        Route::post('/borrowings/{borrowing}/approve', [AdminBorrowingController::class, 'approve'])->name('borrowings.approve');
        Route::post('/borrowings/{borrowing}/reject', [AdminBorrowingController::class, 'reject'])->name('borrowings.reject');
        Route::post('/borrowings/{borrowing}/return', [AdminBorrowingController::class, 'return'])->name('borrowings.return');
        Route::get('/borrowings/{borrowing}/receipt', [AdminBorrowingController::class, 'receipt'])->name('borrowings.receipt');
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('assets', AssetController::class)->except(['show']);
    });
