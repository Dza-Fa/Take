<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\Category;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AssetApiControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_asset_list_returns_requested_fields_and_category_relationship(): void
    {
        $category = $this->createCategory();
        $asset = $this->createAsset($category, [
            'image' => 'data:image/jpeg;base64,'.base64_encode('image-data'),
        ]);

        $this->getJson(route('api.assets.index'))
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Daftar aset berhasil dimuat.',
                'data' => [[
                    'id' => $asset->id,
                    'code' => $asset->code,
                    'name' => $asset->name,
                    'description' => $asset->description,
                    'total_stock' => $asset->total_stock,
                    'available_stock' => $asset->available_stock,
                    'condition' => $asset->condition,
                    'image' => $asset->image,
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ],
                ]],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 12,
                    'total' => 1,
                ],
            ]);
    }

    public function test_asset_list_filters_by_name_or_code_category_and_availability(): void
    {
        $category = $this->createCategory();
        $availableAsset = $this->createAsset($category, [
            'code' => 'TAKE-AST-701',
            'name' => 'Speaker rapat',
            'available_stock' => 2,
        ]);
        $this->createAsset($category, [
            'code' => 'TAKE-AST-702',
            'name' => 'Speaker rusak',
            'available_stock' => 1,
            'condition' => 'rusak',
        ]);
        $otherCategory = $this->createCategory('Alat kerja', 'alat-kerja');
        $this->createAsset($otherCategory, [
            'code' => 'TAKE-AST-703',
            'name' => 'Speaker gudang',
        ]);

        $this->getJson(route('api.assets.index', [
            'q' => 'TAKE-AST-70',
            'category' => $category->slug,
            'available_only' => 1,
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.code', $availableAsset->code)
            ->assertJsonPath('meta.total', 1);

        $this->getJson(route('api.assets.index', ['category' => (string) $category->id]))
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_asset_detail_returns_current_stock_and_404_for_unknown_code(): void
    {
        $asset = $this->createAsset($this->createCategory(), ['available_stock' => 3]);

        $this->getJson(route('api.assets.show', $asset->code))
            ->assertOk()
            ->assertJsonPath('data.code', $asset->code)
            ->assertJsonPath('data.available_stock', 3)
            ->assertJsonPath('data.category.slug', 'perlengkapan-warga');

        $this->getJson(route('api.assets.show', 'TAKE-AST-999'))
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'message' => 'Aset tidak ditemukan.',
                'data' => null,
            ]);
    }

    public function test_returns_422_when_asset_search_query_is_invalid(): void
    {
        $this->getJson(route('api.assets.index', ['available_only' => 'sometimes']))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Parameter pencarian aset tidak valid.')
            ->assertJsonStructure(['data' => ['errors' => ['available_only']]]);
    }

    private function createCategory(string $name = 'Perlengkapan warga', string $slug = 'perlengkapan-warga'): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => $slug,
            'icon' => 'box',
        ]);
    }

    private function createAsset(Category $category, array $attributes = []): Asset
    {
        $assetNumber = Asset::query()->count() + 701;

        return Asset::create(array_merge([
            'category_id' => $category->id,
            'code' => sprintf('TAKE-AST-%03d', $assetNumber),
            'name' => 'Aset warga',
            'description' => 'Perlengkapan bersama warga.',
            'total_stock' => 5,
            'available_stock' => 5,
            'condition' => 'baik',
        ], $attributes));
    }
}
