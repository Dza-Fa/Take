<?php

use App\Http\Controllers\Api\AssetApiController;
use App\Http\Controllers\Api\BorrowingApiController;
use App\Http\Controllers\Api\HolidayApiController;
use App\Http\Controllers\Api\WhatsAppApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('/assets', [AssetApiController::class, 'index'])->name('api.assets.index');
    Route::get('/assets/{code}', [AssetApiController::class, 'show'])->name('api.assets.show');
    Route::get('/borrowings/{bookingCode}', [BorrowingApiController::class, 'show'])->name('api.borrowings.show');
    Route::get('/holidays', [HolidayApiController::class, 'index'])->name('api.holidays.index');

    Route::middleware(['web', 'auth'])->group(function (): void {
        Route::get('/borrowings/{bookingCode}/whatsapp', [WhatsAppApiController::class, 'preview'])
            ->name('api.borrowings.whatsapp.preview');
        Route::post('/borrowings/{bookingCode}/whatsapp/send', [WhatsAppApiController::class, 'send'])
            ->middleware('throttle:10,1')
            ->name('api.borrowings.whatsapp.send');
    });
});
