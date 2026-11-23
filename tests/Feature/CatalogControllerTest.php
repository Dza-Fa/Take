<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_catalog_filters_assets_by_search_category_and_availability(): void
    {
        $category = $this->createCategory();
        $matchingAsset = $this->createAsset($category, [
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
        $this->createAsset($category, [
            'code' => 'TAKE-AST-703',
            'name' => 'Speaker habis',
            'available_stock' => 0,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'warga']))
            ->get(route('catalog.index', [
                'search' => 'TAKE-AST-70',
                'category_id' => $category->id,
                'available' => 1,
            ]))
            ->assertOk()
            ->assertSee($matchingAsset->name)
            ->assertDontSee('Speaker rusak')
            ->assertDontSee('Speaker habis')
            ->assertSee('Tersedia 2 dari 5 unit');
    }

    public function test_catalog_displays_event_bundle_recommendations_and_prefills_quantity(): void
    {
        $category = $this->createCategory();
        $chair = $this->createAsset($category, [
            'code' => 'TAKE-AST-002',
            'name' => 'Kursi Lipat',
            'total_stock' => 40,
            'available_stock' => 25,
        ]);
        $this->createAsset($category, [
            'code' => 'TAKE-AST-003',
            'name' => 'Meja Lipat',
        ]);
        $this->createAsset($category, [
            'code' => 'TAKE-AST-004',
            'name' => 'Speaker Portabel',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'warga']))
            ->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Paket Rapat RT')
            ->assertSee('Paket Kerja Bakti')
            ->assertSee('Paket Syukuran / Hajatan')
            ->assertSee('Kursi Lipat')
            ->assertSee(route('catalog.show', ['asset' => $chair, 'quantity' => 20]), false);
    }

    public function test_asset_detail_renders_the_borrowing_form(): void
    {
        $this->travelTo('2026-11-23 09:00:00');
        $asset = $this->createAsset($this->createCategory(), ['name' => 'Meja rapat']);

        $this->actingAs(User::factory()->create(['role' => 'warga']))
            ->get(route('catalog.show', $asset))
            ->assertOk()
            ->assertSee('Atur jadwal peminjaman')
            ->assertSee('Tanggal pinjam')
            ->assertSee('Tanggal kembali')
            ->assertSee('Keperluan acara')
            ->assertSee('2026-11-23');
    }

    public function test_guest_is_redirected_to_login_from_catalog(): void
    {
        $this->get(route('catalog.index'))
            ->assertRedirect(route('login'));
    }

    private function createCategory(): Category
    {
        $sequence = Category::query()->count() + 1;

        return Category::create([
            'name' => "Perlengkapan warga {$sequence}",
            'slug' => "perlengkapan-warga-{$sequence}",
            'icon' => 'box',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAsset(Category $category, array $attributes = []): Asset
    {
        $sequence = Asset::query()->count() + 701;

        return Asset::create(array_merge([
            'category_id' => $category->id,
            'code' => sprintf('TAKE-AST-%03d', $sequence),
            'name' => 'Aset warga',
            'total_stock' => 5,
            'available_stock' => 5,
            'condition' => 'baik',
        ], $attributes));
    }
}
