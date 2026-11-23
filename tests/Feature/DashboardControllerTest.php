<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_dashboard_shows_inventory_metrics_charts_and_action_lists(): void
    {
        $this->travelTo('2026-11-23 09:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $firstAsset = $this->createAsset(['name' => 'Kursi lipat', 'total_stock' => 8, 'available_stock' => 5]);
        $secondAsset = $this->createAsset(['name' => 'Tenda warga', 'total_stock' => 4, 'available_stock' => 3]);
        $overdue = $this->createBorrowing($firstAsset, [
            'booking_code' => 'TAKE-20261123-101',
            'status' => 'borrowed',
            'quantity' => 2,
            'return_date' => '2026-11-22',
        ]);
        $pending = $this->createBorrowing($secondAsset, [
            'booking_code' => 'TAKE-20261123-102',
            'status' => 'pending',
        ]);
        $this->createBorrowing($firstAsset, [
            'booking_code' => 'TAKE-20261123-103',
            'status' => 'returned',
            'quantity' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHas('stats', [
                'assetTypes' => 2,
                'availableUnits' => 8,
                'activeBorrowings' => 1,
                'pendingBorrowings' => 1,
                'overdueBorrowings' => 1,
            ])
            ->assertViewHas('chartData', [
                'topAssets' => [
                    'labels' => ['Kursi lipat'],
                    'values' => [3],
                ],
                'statuses' => [
                    'labels' => ['Menunggu', 'Disetujui', 'Dipinjam', 'Dikembalikan', 'Ditolak'],
                    'values' => [1, 0, 1, 1, 0],
                ],
            ])
            ->assertSee($overdue->booking_code)
            ->assertSee($pending->booking_code)
            ->assertSee('Barang paling sering dipinjam')
            ->assertSee('Distribusi peminjaman')
            ->assertSee('Kursi lipat')
            ->assertSee('window.takeDashboardCharts');
    }

    public function test_warga_dashboard_shows_only_their_active_borrowings_and_nearest_due_date(): void
    {
        $this->travelTo('2026-11-23 09:00:00');
        $warga = User::factory()->create(['role' => 'warga', 'name' => 'Sari']);
        $otherWarga = User::factory()->create(['role' => 'warga', 'name' => 'Raka']);
        $ownAsset = $this->createAsset(['name' => 'Proyektor warga']);
        $otherAsset = $this->createAsset(['name' => 'Milik warga lain']);
        $ownBorrowing = $this->createBorrowing($ownAsset, [
            'user_id' => $warga->id,
            'booking_code' => 'TAKE-20261123-201',
            'status' => 'borrowed',
            'return_date' => '2026-11-25',
        ]);
        $this->createBorrowing($otherAsset, [
            'user_id' => $otherWarga->id,
            'booking_code' => 'TAKE-20261123-202',
            'status' => 'borrowed',
        ]);
        $this->createBorrowing($ownAsset, [
            'user_id' => $warga->id,
            'booking_code' => 'TAKE-20261123-204',
            'status' => 'borrowed',
            'return_date' => '2026-11-27',
        ]);
        $this->createBorrowing($ownAsset, [
            'user_id' => $warga->id,
            'booking_code' => 'TAKE-20261123-203',
            'status' => 'pending',
        ]);

        $this->actingAs($warga)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHas('activeBorrowings', fn ($borrowings): bool => $borrowings->count() === 2)
            ->assertViewHas('nearestReturn', fn (Borrowing $borrowing): bool => $borrowing->is($ownBorrowing))
            ->assertSee($ownBorrowing->booking_code)
            ->assertSee('TAKE-20261123-204')
            ->assertSee('Proyektor warga')
            ->assertDontSee('Milik warga lain')
            ->assertDontSee('TAKE-20261123-202');
    }

    public function test_empty_admin_dashboard_has_zero_metrics_and_clear_empty_states(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('stats', [
                'assetTypes' => 0,
                'availableUnits' => 0,
                'activeBorrowings' => 0,
                'pendingBorrowings' => 0,
                'overdueBorrowings' => 0,
            ])
            ->assertSee('Belum ada data peminjaman')
            ->assertSee('Belum ada transaksi')
            ->assertSee('Tidak ada pengajuan menunggu')
            ->assertSee('Tidak ada pinjaman terlambat');
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    private function createAsset(array $attributes = []): Asset
    {
        $categoryNumber = Category::query()->count() + 1;
        $category = Category::create([
            'name' => "Perlengkapan warga {$categoryNumber}",
            'slug' => "perlengkapan-warga-{$categoryNumber}",
            'icon' => 'box',
        ]);
        $assetNumber = Asset::query()->count() + 801;

        return Asset::create(array_merge([
            'category_id' => $category->id,
            'code' => sprintf('TAKE-AST-%03d', $assetNumber),
            'name' => 'Aset warga',
            'total_stock' => 5,
            'available_stock' => 5,
            'condition' => 'baik',
        ], $attributes));
    }

    private function createBorrowing(Asset $asset, array $attributes = []): Borrowing
    {
        $user = User::factory()->create(['role' => 'warga']);
        $borrowingNumber = Borrowing::query()->count() + 1;

        return Borrowing::create(array_merge([
            'booking_code' => sprintf('TAKE-20261123-%03d', $borrowingNumber),
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'borrow_date' => '2026-11-23',
            'return_date' => '2026-11-24',
            'purpose' => 'Kegiatan lingkungan',
            'status' => 'pending',
            'stock_reserved' => false,
        ], $attributes));
    }
}
