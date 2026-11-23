<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_render_category_create_and_edit_forms(): void
    {
        $category = Category::create([
            'name' => 'Peralatan Dapur',
            'slug' => 'peralatan-dapur',
            'icon' => 'box',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.categories.create'))
            ->assertOk()
            ->assertSee('Tambah kategori');

        $this->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('Peralatan Dapur');
    }

    public function test_admin_can_create_update_and_delete_a_category_without_assets(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.categories.store'), [
                'name' => 'Perlengkapan Acara',
                'slug' => '',
                'icon' => 'tent',
                'description' => 'Aset untuk kegiatan warga.',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('slug', 'perlengkapan-acara')->firstOrFail();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Perlengkapan Acara',
        ]);

        $this->put(route('admin.categories.update', $category), [
            'name' => 'Perlengkapan Warga',
            'slug' => '',
            'icon' => 'box',
            'description' => 'Diperbarui untuk semua kegiatan.',
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'slug' => 'perlengkapan-warga',
            'icon' => 'box',
        ]);

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_with_assets_cannot_be_deleted(): void
    {
        $category = Category::create([
            'name' => 'Alat Kerja Bakti',
            'slug' => 'alat-kerja-bakti',
            'icon' => 'shovel',
        ]);
        Asset::create([
            'category_id' => $category->id,
            'code' => 'TAKE-AST-901',
            'name' => 'Cangkul',
            'total_stock' => 3,
            'available_stock' => 3,
            'condition' => 'baik',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas(
                'error',
                'Kategori "Alat Kerja Bakti" masih memiliki 1 aset. Pindahkan atau hapus aset tersebut terlebih dahulu.'
            );

        $this->assertModelExists($category);
    }

    public function test_warga_cannot_access_category_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'warga']))
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_category_names_are_escaped_in_the_admin_list(): void
    {
        Category::create([
            'name' => '<script>alert(1)</script>',
            'slug' => 'script-category',
            'icon' => 'box',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
