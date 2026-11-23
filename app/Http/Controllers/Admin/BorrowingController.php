<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectBorrowingRequest;
use App\Http\Requests\Admin\ReturnBorrowingRequest;
use App\Models\Asset;
use App\Models\Borrowing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BorrowingController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'borrowed', 'returned', 'rejected'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['all', ...self::STATUSES])],
            'overdue' => ['nullable', 'boolean'],
        ], [
            'status.in' => 'Filter status peminjaman tidak valid.',
            'overdue.boolean' => 'Filter keterlambatan tidak valid.',
        ]);

        $status = $filters['status'] ?? 'all';
        $isOverdueFilter = $request->boolean('overdue');

        $borrowingsQuery = Borrowing::query()
            ->with(['user', 'asset.category']);

        if ($status !== 'all') {
            $borrowingsQuery->where('status', $status);
        }

        if ($isOverdueFilter) {
            $borrowingsQuery
                ->whereIn('status', ['approved', 'borrowed'])
                ->whereDate('return_date', '<', today());

            $borrowingsQuery->orderBy('return_date')->orderBy('id');
        } else {
            $borrowingsQuery->orderByDesc('created_at')->orderByDesc('id');
        }

        $borrowings = $borrowingsQuery->paginate(12)->withQueryString();

        return view('admin.borrowings.index', compact('borrowings', 'status', 'isOverdueFilter'));
    }

    public function approve(Borrowing $borrowing): RedirectResponse
    {
        DB::transaction(function () use ($borrowing): void {
            $lockedBorrowing = Borrowing::query()
                ->whereKey($borrowing->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBorrowing->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pengajuan berstatus menunggu yang dapat disetujui.',
                ]);
            }

            $asset = Asset::query()
                ->whereKey($lockedBorrowing->asset_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($asset->condition === 'rusak') {
                throw ValidationException::withMessages([
                    'status' => 'Aset dalam kondisi rusak dan tidak dapat diserahkan.',
                ]);
            }

            $availableStockForApproval = $asset->available_stock
                + ($lockedBorrowing->stock_reserved ? $lockedBorrowing->quantity : 0);

            if ($availableStockForApproval < $lockedBorrowing->quantity) {
                throw ValidationException::withMessages([
                    'status' => 'Stok tersedia tidak mencukupi. Pengajuan tetap menunggu dan tidak ada stok yang berubah.',
                ]);
            }

            if (! $lockedBorrowing->stock_reserved) {
                $asset->decrement('available_stock', $lockedBorrowing->quantity);
            }

            $lockedBorrowing->update([
                'status' => 'borrowed',
                'stock_reserved' => true,
            ]);
        });

        return redirect()->route('admin.borrowings.index')
            ->with('success', "Pengajuan {$borrowing->booking_code} disetujui dan barang telah diserahkan.");
    }

    public function reject(RejectBorrowingRequest $request, Borrowing $borrowing): RedirectResponse
    {
        DB::transaction(function () use ($request, $borrowing): void {
            $lockedBorrowing = Borrowing::query()
                ->whereKey($borrowing->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBorrowing->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pengajuan berstatus menunggu yang dapat ditolak.',
                ]);
            }

            if ($lockedBorrowing->stock_reserved) {
                $asset = Asset::query()
                    ->whereKey($lockedBorrowing->asset_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $updatedAvailableStock = $asset->available_stock + $lockedBorrowing->quantity;

                if ($updatedAvailableStock > $asset->total_stock) {
                    throw ValidationException::withMessages([
                        'status' => 'Stok tersedia akan melebihi stok total saat reservasi dilepas. Periksa pencatatan stok terlebih dahulu.',
                    ]);
                }

                $asset->update(['available_stock' => $updatedAvailableStock]);
            }

            $lockedBorrowing->update([
                'status' => 'rejected',
                'admin_note' => $request->validated('admin_note'),
                'stock_reserved' => false,
            ]);
        });

        return redirect()->route('admin.borrowings.index')
            ->with('success', "Pengajuan {$borrowing->booking_code} berhasil ditolak.");
    }

    public function return(ReturnBorrowingRequest $request, Borrowing $borrowing): RedirectResponse
    {
        DB::transaction(function () use ($request, $borrowing): void {
            $lockedBorrowing = Borrowing::query()
                ->whereKey($borrowing->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedBorrowing->status, ['approved', 'borrowed'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transaksi yang sudah diserahkan yang dapat diproses pengembaliannya.',
                ]);
            }

            $asset = Asset::query()
                ->whereKey($lockedBorrowing->asset_id)
                ->lockForUpdate()
                ->firstOrFail();

            $updatedAvailableStock = $asset->available_stock + $lockedBorrowing->quantity;

            if ($updatedAvailableStock > $asset->total_stock) {
                throw ValidationException::withMessages([
                    'status' => 'Stok tersedia akan melebihi stok total. Periksa kembali pencatatan stok sebelum menerima pengembalian.',
                ]);
            }

            $asset->update([
                'available_stock' => $updatedAvailableStock,
                'condition' => match ($request->validated('return_condition')) {
                    'rusak' => 'rusak',
                    'perbaikan' => $asset->condition === 'rusak' ? 'rusak' : 'perbaikan',
                    default => $asset->condition,
                },
            ]);

            $lockedBorrowing->update([
                'status' => 'returned',
                'returned_at' => now(),
                'return_condition' => $request->validated('return_condition'),
                'admin_note' => $request->validated('admin_note'),
                'stock_reserved' => false,
            ]);
        });

        return redirect()->route('admin.borrowings.index')
            ->with('success', "Pengembalian {$borrowing->booking_code} berhasil dicatat.");
    }

    public function receipt(Borrowing $borrowing): View
    {
        $borrowing->loadMissing(['user', 'asset.category']);

        return view('borrowings.receipt', compact('borrowing'));
    }
}
