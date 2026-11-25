<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssetApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
            'available_only' => ['nullable', Rule::in(['0', '1', 'true', 'false', 'on', 'off'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'q.string' => 'Pencarian aset harus berupa teks.',
            'q.max' => 'Pencarian aset maksimal 100 karakter.',
            'category.string' => 'Kategori harus berupa slug atau ID.',
            'category.max' => 'Nilai kategori maksimal 255 karakter.',
            'available_only.in' => 'Filter ketersediaan harus bernilai 0 atau 1.',
            'page.integer' => 'Nomor halaman harus berupa angka.',
            'page.min' => 'Nomor halaman minimal 1.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter pencarian aset tidak valid.',
                'data' => ['errors' => $validator->errors()],
            ], 422);
        }

        $filters = $validator->validated();
        $assetsQuery = Asset::query()->with('category:id,name,slug');

        if (filled($filters['q'] ?? null)) {
            $search = $filters['q'];

            $assetsQuery->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (filled($filters['category'] ?? null)) {
            $category = $filters['category'];

            $assetsQuery->whereHas('category', function (Builder $query) use ($category): void {
                $query->where('slug', $category);

                if (ctype_digit($category)) {
                    $query->orWhereKey((int) $category);
                }
            });
        }

        if ($request->boolean('available_only')) {
            $assetsQuery->where('available_stock', '>', 0)
                ->where('condition', '!=', 'rusak');
        }

        $assets = $assetsQuery
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Daftar aset berhasil dimuat.',
            'data' => $assets->getCollection()
                ->map(fn (Asset $asset): array => $this->assetData($asset))
                ->values(),
            'meta' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
        ]);
    }

    public function show(string $code): JsonResponse
    {
        $asset = Asset::query()
            ->with('category:id,name,slug')
            ->where('code', strtoupper($code))
            ->first();

        if ($asset === null) {
            return response()->json([
                'success' => false,
                'message' => 'Aset tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail aset berhasil dimuat.',
            'data' => $this->assetData($asset),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function assetData(Asset $asset): array
    {
        return [
            'id' => $asset->id,
            'code' => $asset->code,
            'name' => $asset->name,
            'description' => $asset->description,
            'total_stock' => $asset->total_stock,
            'available_stock' => $asset->available_stock,
            'condition' => $asset->condition,
            'image' => $asset->image_url,
            'category' => [
                'id' => $asset->category->id,
                'name' => $asset->category->name,
                'slug' => $asset->category->slug,
            ],
        ];
    }
}
