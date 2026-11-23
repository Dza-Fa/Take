<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_render_asset_create_and_edit_forms(): void
    {
        Storage::fake('public');
        $category = $this->createCategory();
        $image = UploadedFile::fake()->image('asset.png')->storePublicly('assets', 'public');
        $asset = $this->createAsset($category, ['image' => $image]);
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.assets.create'))
            ->assertOk()
            ->assertSee('Tambah aset');

        $this->get(route('admin.assets.edit', $asset))
            ->assertOk()
            ->assertSee('Aset warga');
    }

    public function test_admin_can_create_an_asset_with_a_generated_code_and_photo(): void
    {
        Storage::fake('public');
        $category = $this->createCategory();

        $this->actingAs($this->createAdmin())
            ->post(route('admin.assets.store'), [
                'category_id' => $category->id,
                'name' => 'Speaker warga',
                'description' => 'Speaker portabel untuk kegiatan warga.',
                'total_stock' => 2,
                'available_stock' => 2,
                'condition' => 'baik',
                'image' => UploadedFile::fake()->image('speaker.png'),
            ])
            ->assertRedirect(route('admin.assets.index'));

        $asset = Asset::query()->where('name', 'Speaker warga')->firstOrFail();

        $this->assertSame('TAKE-AST-001', $asset->code);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'category_id' => $category->id,
            'total_stock' => 2,
            'available_stock' => 2,
        ]);
        Storage::disk('public')->assertExists($asset->image);
    }

    public function test_stock_available_cannot_exceed_total_stock(): void
    {
        $category = $this->createCategory();

        $this->actingAs($this->createAdmin())
            ->from(route('admin.assets.create'))
            ->post(route('admin.assets.store'), [
                'category_id' => $category->id,
                'name' => 'Stok tidak konsisten',
                'total_stock' => 2,
                'available_stock' => 3,
                'condition' => 'baik',
            ])
            ->assertSessionHasErrors([
                'available_stock' => 'Stok tersedia tidak boleh melebihi stok total.',
            ]);

        $this->assertDatabaseMissing('assets', ['name' => 'Stok tidak konsisten']);
    }

    public function test_total_stock_cannot_be_less_than_available_and_borrowed_stock(): void
    {
        $category = $this->createCategory();
        $asset = $this->createAsset($category, [
            'total_stock' => 10,
            'available_stock' => 6,
        ]);
        $user = User::factory()->create(['role' => 'warga']);

        Borrowing::create([
            'booking_code' => 'TAKE-20261123-901',
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'quantity' => 3,
            'borrow_date' => '2026-11-20',
            'return_date' => '2026-11-25',
            'purpose' => 'Rapat warga',
            'status' => 'borrowed',
        ]);

        $this->actingAs($this->createAdmin())
            ->from(route('admin.assets.edit', $asset))
            ->put(route('admin.assets.update', $asset), [
                'category_id' => $category->id,
                'code' => $asset->code,
                'name' => $asset->name,
                'total_stock' => 8,
                'available_stock' => 6,
                'condition' => 'baik',
            ])
            ->assertSessionHasErrors([
                'total_stock' => 'Stok total harus mencakup stok tersedia dan aset yang sedang dipinjam.',
            ]);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'total_stock' => 10,
            'available_stock' => 6,
        ]);
    }

    public function test_replacing_an_asset_photo_removes_the_old_file(): void
    {
        Storage::fake('public');
        $category = $this->createCategory();
        $oldImage = UploadedFile::fake()->image('old.png')->storePublicly('assets', 'public');
        $asset = $this->createAsset($category, ['image' => $oldImage]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.assets.update', $asset), [
                'category_id' => $category->id,
                'code' => $asset->code,
                'name' => $asset->name,
                'total_stock' => $asset->total_stock,
                'available_stock' => $asset->available_stock,
                'condition' => $asset->condition,
                'image' => UploadedFile::fake()->image('new.png'),
            ])
            ->assertRedirect(route('admin.assets.index'));

        $asset->refresh();

        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($asset->image);
    }

    public function test_deleting_an_asset_removes_its_photo(): void
    {
        Storage::fake('public');
        $category = $this->createCategory();
        $image = UploadedFile::fake()->image('asset.png')->storePublicly('assets', 'public');
        $asset = $this->createAsset($category, ['image' => $image]);

        $this->actingAs($this->createAdmin())
            ->delete(route('admin.assets.destroy', $asset))
            ->assertRedirect(route('admin.assets.index'));

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
        Storage::disk('public')->assertMissing($image);
    }

    public function test_asset_filters_apply_category_condition_and_search(): void
    {
        $category = $this->createCategory();
        $this->createAsset($category, [
            'code' => 'TAKE-AST-801',
            'name' => 'Speaker portabel',
            'condition' => 'perbaikan',
        ]);
        $this->createAsset($category, [
            'code' => 'TAKE-AST-802',
            'name' => 'Kursi lipat',
            'condition' => 'baik',
        ]);

        $this->actingAs($this->createAdmin())
            ->get(route('admin.assets.index', [
                'category_id' => $category->id,
                'condition' => 'perbaikan',
                'search' => 'TAKE-AST-801',
            ]))
            ->assertOk()
            ->assertSee('Speaker portabel')
            ->assertDontSee('Kursi lipat');
    }

    private function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createCategory(): Category
    {
        return Category::create([
            'name' => 'Audio warga',
            'slug' => 'audio-warga',
            'icon' => 'speaker',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAsset(Category $category, array $attributes = []): Asset
    {
        return Asset::create(array_merge([
            'category_id' => $category->id,
            'code' => 'TAKE-AST-800',
            'name' => 'Aset warga',
            'total_stock' => 5,
            'available_stock' => 5,
            'condition' => 'baik',
        ], $attributes));
    }
}
