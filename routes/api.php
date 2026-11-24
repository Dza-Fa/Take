<?php

use App\Http\Controllers\Api\AssetApiController;
use App\Http\Controllers\Api\BorrowingApiController;
use App\Http\Controllers\Api\HolidayApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('/assets', [AssetApiController::class, 'index'])->name('api.assets.index');
    Route::get('/assets/{code}', [AssetApiController::class, 'show'])->name('api.assets.show');
    Route::get('/borrowings/{bookingCode}', [BorrowingApiController::class, 'show'])->name('api.borrowings.show');
    Route::get('/holidays', [HolidayApiController::class, 'index'])->name('api.holidays.index');
});
