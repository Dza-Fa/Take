<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IndonesianHolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HolidayApiController extends Controller
{
    public function index(Request $request, IndonesianHolidayService $holidays): JsonResponse
    {
        $validator = Validator::make([
            'year' => $request->query('year', now()->year),
        ], [
            'year' => ['required', 'integer', 'between:2000,2100'],
        ], [
            'year.required' => 'Tahun wajib diisi.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.between' => 'Tahun harus berada di antara 2000 dan 2100.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tahun tidak valid.',
                'data' => ['errors' => $validator->errors()],
            ], 422);
        }

        $validated = $validator->validated();
        $year = (int) $validated['year'];
        $result = $holidays->forYear($year);

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Tanggal libur nasional berhasil dimuat.',
            'data' => [
                'year' => $year,
                ...$result,
            ],
        ]);
    }
}
