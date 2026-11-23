<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    private const EVENT_BUNDLES = [
        'rapat' => [
            'label' => 'Paket Rapat RT',
            'description' => 'Perlengkapan dasar untuk rapat dan pertemuan warga.',
            'items' => [
                ['code' => 'TAKE-AST-002', 'quantity' => 20],
                ['code' => 'TAKE-AST-003', 'quantity' => 2],
                ['code' => 'TAKE-AST-004', 'quantity' => 1],
            ],
        ],
        'kerja-bakti' => [
            'label' => 'Paket Kerja Bakti',
            'description' => 'Alat bantu untuk kegiatan bersih lingkungan.',
            'items' => [
                ['code' => 'TAKE-AST-006', 'quantity' => 4],
                ['code' => 'TAKE-AST-007', 'quantity' => 1],
                ['code' => 'TAKE-AST-008', 'quantity' => 1],
            ],
        ],
        'syukuran' => [
            'label' => 'Paket Syukuran / Hajatan',
            'description' => 'Perlengkapan untuk acara warga dan jamuan bersama.',
            'items' => [
                ['code' => 'TAKE-AST-001', 'quantity' => 2],
                ['code' => 'TAKE-AST-002', 'quantity' => 30],
                ['code' => 'TAKE-AST-009', 'quantity' => 1],
                ['code' => 'TAKE-AST-010', 'quantity' => 1],
            ],
        ],
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'available' => ['nullable', 'boolean'],
        ]);

        $assetsQuery = Asset::query()->with('category');

        if (filled($filters['search'] ?? null)) {
            $search = $filters['search'];

            $assetsQuery->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (filled($filters['category_id'] ?? null)) {
            $assetsQuery->where('category_id', $filters['category_id']);
        }

        if ($request->boolean('available')) {
            $assetsQuery->where('available_stock', '>', 0)
                ->where('condition', '!=', 'rusak');
        }

        $assets = $assetsQuery
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $recommendedCodes = collect(self::EVENT_BUNDLES)
            ->flatMap(fn (array $bundle): array => array_column($bundle['items'], 'code'))
            ->unique()
            ->all();

        $bundleAssets = Asset::query()
            ->with('category')
            ->whereIn('code', $recommendedCodes)
            ->get()
            ->keyBy('code');

        $bundles = collect(self::EVENT_BUNDLES)
            ->map(function (array $bundle) use ($bundleAssets): array {
                $bundle['recommendations'] = collect($bundle['items'])
                    ->map(function (array $item) use ($bundleAssets): ?array {
                        $asset = $bundleAssets->get($item['code']);

                        if ($asset === null) {
                            return null;
                        }

                        return [
                            'asset' => $asset,
                            'quantity' => $item['quantity'],
                        ];
                    })
                    ->filter()
                    ->values();

                return $bundle;
            });

        return view('catalog.index', [
            'assets' => $assets,
            'categories' => Category::query()->orderBy('name')->get(),
            'filters' => $filters,
            'bundles' => $bundles,
        ]);
    }

    public function show(Request $request, Asset $asset): View
    {
        $asset->loadMissing('category');

        $suggestedQuantity = max(1, $request->integer('quantity', 1));

        if ($asset->available_stock > 0) {
            $suggestedQuantity = min($suggestedQuantity, $asset->available_stock);
        }

        return view('catalog.show', compact('asset', 'suggestedQuantity'));
    }
}
