<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Category;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

            $loanTrend = $this->buildSevenDayTrend(Borrowing::query());

            $actionableBorrowings = Borrowing::query()
                ->with(['user', 'asset'])
                ->whereIn('status', ['pending', 'approved', 'borrowed'])
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderBy('return_date')
                ->limit(8)
                ->get();

            $assetStockProgress = Asset::query()
                ->with('category')
                ->where('total_stock', '>', 0)
                ->orderByRaw('available_stock * 1.0 / total_stock ASC')
                ->limit(4)
                ->get();

            return view('admin.dashboard', [
                'stats' => $stats,
                'pendingBorrowings' => $pendingBorrowings,
                'overdueBorrowings' => $overdueBorrowings,
                'actionableBorrowings' => $actionableBorrowings,
                'assetStockProgress' => $assetStockProgress,
                'loanTrend' => $loanTrend,
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
            ->with('asset.category')
            ->whereIn('status', self::BORROWED_STATUSES)
            ->orderBy('return_date')
            ->orderBy('id')
            ->get();

        $quickAssets = Asset::query()
            ->with('category')
            ->where('condition', '!=', 'rusak')
            ->where('available_stock', '>', 0)
            ->orderBy('name')
            ->get();

        $categoryDistribution = Category::query()
            ->withSum(['assets as available_units' => fn ($query) => $query->where('condition', '!=', 'rusak')], 'available_stock')
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $category): bool => (int) ($category->available_units ?? 0) > 0)
            ->values();

        return view('dashboard', [
            'activeBorrowings' => $activeBorrowings,
            'nearestReturn' => $activeBorrowings->first(),
            'quickAssets' => $quickAssets,
            'totalAvailableUnits' => (int) $quickAssets->sum('available_stock'),
            'wargaChartData' => [
                'loanTrend' => $this->buildSevenDayTrend($request->user()->borrowings()->getQuery()),
                'categories' => [
                    'labels' => $categoryDistribution->pluck('name')->all(),
                    'values' => $categoryDistribution->map(fn (Category $category): int => (int) $category->available_units)->all(),
                ],
            ],
        ]);
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function buildSevenDayTrend($query): array
    {
        $startDate = today()->subDays(6);
        $endDate = today();

        /** @var Collection<string, int> $counts */
        $counts = (clone $query)
            ->whereDate('borrow_date', '>=', $startDate)
            ->whereDate('borrow_date', '<=', $endDate)
            ->selectRaw('DATE(borrow_date) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->toDateString();
            $labels[] = $date->format('d/m');
            $values[] = (int) $counts->get($key, 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
