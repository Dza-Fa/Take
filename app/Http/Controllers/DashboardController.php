<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const BORROWED_STATUSES = ['approved', 'borrowed'];

    public function index(Request $request): View
    {
        if ($request->user()->isAdmin()) {
            $statusCounts = Borrowing::query()
                ->select('status')
                ->selectRaw('COUNT(*) AS total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $stats = [
                'assetTypes' => Asset::query()->count(),
                'availableUnits' => (int) Asset::query()->sum('available_stock'),
                'activeBorrowings' => (int) $statusCounts->get('approved', 0) + (int) $statusCounts->get('borrowed', 0),
                'pendingBorrowings' => (int) $statusCounts->get('pending', 0),
                'overdueBorrowings' => Borrowing::query()
                    ->whereIn('status', self::BORROWED_STATUSES)
                    ->whereDate('return_date', '<', today())
                    ->count(),
            ];

            $pendingBorrowings = Borrowing::query()
                ->with(['user', 'asset'])
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            $overdueBorrowings = Borrowing::query()
                ->with(['user', 'asset'])
                ->whereIn('status', self::BORROWED_STATUSES)
                ->whereDate('return_date', '<', today())
                ->orderBy('return_date')
                ->orderBy('id')
                ->limit(5)
                ->get();

            $topAssets = Borrowing::query()
                ->with('asset:id,name')
                ->whereIn('status', ['approved', 'borrowed', 'returned'])
                ->select('asset_id')
                ->selectRaw('SUM(quantity) AS total_units')
                ->groupBy('asset_id')
                ->orderByDesc('total_units')
                ->limit(5)
                ->get();

            $statusLabels = [
                'pending' => 'Menunggu',
                'approved' => 'Disetujui',
                'borrowed' => 'Dipinjam',
                'returned' => 'Dikembalikan',
                'rejected' => 'Ditolak',
            ];

            return view('admin.dashboard', [
                'stats' => $stats,
                'pendingBorrowings' => $pendingBorrowings,
                'overdueBorrowings' => $overdueBorrowings,
                'chartData' => [
                    'topAssets' => [
                        'labels' => $topAssets->map(fn (Borrowing $borrowing): string => $borrowing->asset->name)->all(),
                        'values' => $topAssets->map(fn (Borrowing $borrowing): int => (int) $borrowing->total_units)->all(),
                    ],
                    'statuses' => [
                        'labels' => array_values($statusLabels),
                        'values' => array_map(
                            fn (string $status): int => (int) $statusCounts->get($status, 0),
                            array_keys($statusLabels)
                        ),
                    ],
                ],
            ]);
        }

        $activeBorrowings = $request->user()
            ->borrowings()
            ->with('asset')
            ->whereIn('status', self::BORROWED_STATUSES)
            ->orderBy('return_date')
            ->orderBy('id')
            ->get();

        return view('dashboard', [
            'activeBorrowings' => $activeBorrowings,
            'nearestReturn' => $activeBorrowings->first(),
        ]);
    }
}
