<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WhatsAppApiController extends Controller
{
    private const MESSAGE_TYPES = ['new_request', 'status', 'reminder'];

    public function preview(Request $request, string $bookingCode, WhatsAppService $whatsApp): JsonResponse
    {
        $type = $request->query('type');

        if (! is_string($type) || ! in_array($type, self::MESSAGE_TYPES, true)) {
            return $this->error('Jenis pesan WhatsApp tidak valid.', 422);
        }

        $borrowing = $this->findBorrowing($bookingCode);

        if ($borrowing === null || ! $this->mayUseMessageType($request, $borrowing, $type)) {
            return $this->error('Peminjaman tidak ditemukan.', 404);
        }

        try {
            $data = $whatsApp->preview($borrowing, $type);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pratinjau pesan WhatsApp berhasil dibuat.',
            'data' => $data,
        ]);
    }

    public function send(Request $request, string $bookingCode, WhatsAppService $whatsApp): JsonResponse
    {
        $type = $request->input('type');

        if (! is_string($type) || ! in_array($type, self::MESSAGE_TYPES, true)) {
            return $this->error('Jenis pesan WhatsApp tidak valid.', 422);
        }

        $borrowing = $this->findBorrowing($bookingCode);

        if ($borrowing === null || ! $this->mayUseMessageType($request, $borrowing, $type)) {
            return $this->error('Peminjaman tidak ditemukan.', 404);
        }

        try {
            $data = $whatsApp->send($borrowing, $type);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $message = $data['sent']
            ? 'Pesan WhatsApp berhasil dikirim melalui gateway.'
            : 'Gateway tidak tersedia. Lanjutkan melalui tautan WhatsApp.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function findBorrowing(string $bookingCode): ?Borrowing
    {
        if (preg_match('/^TAKE-\d{8}-\d{3,}$/i', $bookingCode) !== 1) {
            return null;
        }

        return Borrowing::query()
            ->with(['user', 'asset'])
            ->where('booking_code', strtoupper($bookingCode))
            ->first();
    }

    private function mayUseMessageType(Request $request, Borrowing $borrowing, string $type): bool
    {
        $user = $request->user();

        return match ($type) {
            'new_request' => ! $user->isAdmin()
                && $borrowing->user_id === $user->getAuthIdentifier()
                && $borrowing->status === 'pending',
            'status' => $user->isAdmin()
                && in_array($borrowing->status, ['approved', 'borrowed', 'rejected'], true),
            'reminder' => $user->isAdmin()
                && in_array($borrowing->status, ['approved', 'borrowed'], true),
        };
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
        ], $status);
    }
}
