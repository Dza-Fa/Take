<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use Illuminate\Http\JsonResponse;

class BorrowingApiController extends Controller
{
    public function show(string $bookingCode): JsonResponse
    {
        if (preg_match('/^TAKE-\d{8}-\d{3,}$/', $bookingCode) !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Format kode peminjaman tidak valid.',
                'data' => null,
            ], 422);
        }

        $borrowing = Borrowing::query()
            ->with('asset:id,name')
            ->where('booking_code', strtoupper($bookingCode))
            ->first();

        if ($borrowing === null) {
            return response()->json([
                'success' => false,
                'message' => 'Peminjaman tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status peminjaman berhasil dimuat.',
            'data' => [
                'booking_code' => $borrowing->booking_code,
                'asset_name' => $borrowing->asset->name,
                'quantity' => $borrowing->quantity,
                'borrow_date' => $borrowing->borrow_date->toDateString(),
                'return_date' => $borrowing->return_date->toDateString(),
                'status' => $borrowing->status,
                'is_overdue' => $borrowing->isOverdue(),
            ],
        ]);
    }
}
