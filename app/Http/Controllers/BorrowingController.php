<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitBorrowingRequest;
use App\Models\Asset;
use App\Models\Borrowing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BorrowingController extends Controller
{
    public function store(SubmitBorrowingRequest $request, Asset $asset): RedirectResponse
    {
        $quantity = $request->integer('quantity');
        $bookingDate = now()->format('Ymd');
        $datePrefix = 'TAKE-'.$bookingDate.'-';

        $borrowing = Cache::lock('take:booking-code:'.$bookingDate, 60)
            ->block(10, function () use ($asset, $datePrefix, $quantity, $request): Borrowing {
                return DB::transaction(function () use ($asset, $datePrefix, $quantity, $request): Borrowing {
                    $lockedAsset = Asset::query()
                        ->whereKey($asset->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedAsset->condition === 'rusak') {
                        throw ValidationException::withMessages([
                            'quantity' => 'Aset dalam kondisi rusak dan tidak dapat dipinjam.',
                        ]);
                    }

                    if ($quantity > $lockedAsset->available_stock) {
                        throw ValidationException::withMessages([
                            'quantity' => 'Stok tersedia berubah. Silakan sesuaikan jumlah pinjaman.',
                        ]);
                    }

                    $borrowing = Borrowing::create([
                        'booking_code' => $this->nextBookingCode($datePrefix),
                        'user_id' => $request->user()->getAuthIdentifier(),
                        'asset_id' => $lockedAsset->getKey(),
                        'quantity' => $quantity,
                        'borrow_date' => $request->validated('borrow_date'),
                        'return_date' => $request->validated('return_date'),
                        'purpose' => $request->validated('purpose'),
                        'status' => 'pending',
                    ]);

                    $lockedAsset->decrement('available_stock', $quantity);

                    return $borrowing;
                });
            });

        return redirect()->route('my-borrowings.index')
            ->with('success', "Pengajuan berhasil dikirim dengan kode {$borrowing->booking_code}. Stok telah dicadangkan sementara menunggu persetujuan admin.");
    }

    public function index(Request $request): View
    {
        $borrowings = $request->user()
            ->borrowings()
            ->with('asset.category')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('borrowings.index', compact('borrowings'));
    }

    public function receipt(Request $request, Borrowing $borrowing): View
    {
        abort_unless($borrowing->user_id === $request->user()->getAuthIdentifier(), 404);

        $borrowing->loadMissing('asset.category');

        return view('borrowings.receipt', compact('borrowing'));
    }

    private function nextBookingCode(string $datePrefix): string
    {
        $latestBookingCode = Borrowing::query()
            ->where('booking_code', 'like', $datePrefix.'%')
            ->orderByDesc('booking_code')
            ->value('booking_code');

        $sequence = $latestBookingCode === null
            ? 1
            : (int) substr($latestBookingCode, strlen($datePrefix)) + 1;

        do {
            $bookingCode = $datePrefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Borrowing::query()->where('booking_code', $bookingCode)->exists());

        return $bookingCode;
    }
}
