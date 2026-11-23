<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BorrowingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_filter_borrowings_by_status_and_overdue_state(): void
    {
        $this->travelTo('2026-11-23 10:00:00');
        $admin = $this->createAdmin();
        $asset = $this->createAsset();
        $borrowed = $this->createBorrowing($asset, ['status' => 'borrowed', 'return_date' => '2026-11-22']);
        $pending = $this->createBorrowing($asset, [
            'booking_code' => 'TAKE-20261123-002',
            'status' => 'pending',
            'return_date' => '2026-11-24',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.borrowings.index', ['status' => 'all', 'overdue' => 1]))
            ->assertOk()
            ->assertSee($borrowed->booking_code)
            ->assertSee('Terlambat')
            ->assertDontSee($pending->booking_code);

        $this->get(route('admin.borrowings.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee($pending->booking_code)
            ->assertDontSee($borrowed->booking_code);
    }

    public function test_admin_approval_rechecks_stock_and_marks_borrowed(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['available_stock' => 5]);
        $borrowing = $this->createBorrowing($asset, ['quantity' => 3]);

        $this->actingAs($admin)
            ->post(route('admin.borrowings.approve', $borrowing))
            ->assertRedirect(route('admin.borrowings.index'))
            ->assertSessionHas('success', "Pengajuan {$borrowing->booking_code} disetujui dan barang telah diserahkan.");

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'borrowed',
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 2,
        ]);
    }

    public function test_admin_approval_keeps_pending_request_and_stock_when_quantity_is_no_longer_available(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['available_stock' => 2]);
        $borrowing = $this->createBorrowing($asset, ['quantity' => 3]);

        $this->actingAs($admin)
            ->from(route('admin.borrowings.index'))
            ->post(route('admin.borrowings.approve', $borrowing))
            ->assertRedirect(route('admin.borrowings.index'))
            ->assertSessionHasErrors([
                'status' => 'Stok tersedia tidak mencukupi. Pengajuan tetap menunggu dan tidak ada stok yang berubah.',
            ]);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 2,
        ]);
    }

    public function test_admin_cannot_approve_the_same_borrowing_twice(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['available_stock' => 5]);
        $borrowing = $this->createBorrowing($asset, ['quantity' => 2]);
        $this->actingAs($admin)
            ->post(route('admin.borrowings.approve', $borrowing))
            ->assertRedirect(route('admin.borrowings.index'));

        $this->from(route('admin.borrowings.index'))
            ->post(route('admin.borrowings.approve', $borrowing))
            ->assertRedirect(route('admin.borrowings.index'))
            ->assertSessionHasErrors([
                'status' => 'Hanya pengajuan berstatus menunggu yang dapat disetujui.',
            ]);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'borrowed',
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 3,
        ]);
    }

    public function test_approval_does_not_deduct_stock_a_second_time_for_a_legacy_reserved_request(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['total_stock' => 5, 'available_stock' => 2]);
        $borrowing = $this->createBorrowing($asset, [
            'quantity' => 3,
            'stock_reserved' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.borrowings.approve', $borrowing))
            ->assertRedirect(route('admin.borrowings.index'));

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'borrowed',
            'stock_reserved' => true,
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 2,
        ]);
    }

    public function test_admin_cannot_reject_a_pending_request_without_a_reason(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset();
        $borrowing = $this->createBorrowing($asset);

        $this->actingAs($admin)
            ->from(route('admin.borrowings.index'))
            ->post(route('admin.borrowings.reject', $borrowing), ['admin_note' => ''])
            ->assertRedirect(route('admin.borrowings.index'))
            ->assertSessionHasErrors([
                'admin_note' => 'Alasan penolakan wajib diisi.',
            ]);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'pending',
            'admin_note' => null,
        ]);
    }

    public function test_admin_rejection_saves_the_reason_without_changing_asset_stock(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['available_stock' => 4]);
        $borrowing = $this->createBorrowing($asset);

        $this->actingAs($admin)
            ->post(route('admin.borrowings.reject', $borrowing), [
                'admin_note' => 'Jadwal kegiatan berbenturan dengan agenda warga.',
            ])
            ->assertRedirect(route('admin.borrowings.index'));

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'rejected',
            'admin_note' => 'Jadwal kegiatan berbenturan dengan agenda warga.',
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 4,
        ]);
    }

    public function test_rejecting_a_legacy_reserved_request_releases_its_stock(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['total_stock' => 5, 'available_stock' => 2]);
        $borrowing = $this->createBorrowing($asset, [
            'quantity' => 3,
            'stock_reserved' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.borrowings.reject', $borrowing), [
                'admin_note' => 'Jadwal kegiatan berbenturan.',
            ])
            ->assertRedirect(route('admin.borrowings.index'));

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'rejected',
            'stock_reserved' => false,
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 5,
        ]);
    }

    public function test_admin_records_return_and_restores_stock_for_a_good_asset(): void
    {
        $this->travelTo('2026-11-23 10:15:00');
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['total_stock' => 5, 'available_stock' => 2]);
        $borrowing = $this->createBorrowing($asset, [
            'status' => 'borrowed',
            'quantity' => 3,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.borrowings.return', $borrowing), [
                'return_condition' => 'baik',
                'admin_note' => 'Barang diterima lengkap.',
            ])
            ->assertRedirect(route('admin.borrowings.index'));

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'returned',
            'returned_at' => '2026-11-23 10:15:00',
            'return_condition' => 'baik',
            'admin_note' => 'Barang diterima lengkap.',
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 5,
            'condition' => 'baik',
        ]);
    }

    public function test_admin_cannot_record_a_return_without_a_condition(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['available_stock' => 4]);
        $borrowing = $this->createBorrowing($asset, ['quantity' => 1, 'status' => 'borrowed']);

        $this->actingAs($admin)
            ->from(route('admin.borrowings.index'))
            ->post(route('admin.borrowings.return', $borrowing), ['return_condition' => ''])
            ->assertRedirect(route('admin.borrowings.index'))
            ->assertSessionHasErrors([
                'return_condition' => 'Pilih kondisi aset saat dikembalikan.',
            ]);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'borrowed',
            'returned_at' => null,
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 4,
        ]);
    }

    public function test_admin_marks_asset_for_repair_when_returned_condition_is_perbaikan(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['available_stock' => 4]);
        $borrowing = $this->createBorrowing($asset, ['quantity' => 1, 'status' => 'borrowed']);

        $this->actingAs($admin)
            ->post(route('admin.borrowings.return', $borrowing), ['return_condition' => 'perbaikan'])
            ->assertRedirect(route('admin.borrowings.index'));

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 5,
            'condition' => 'perbaikan',
        ]);
        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'returned',
            'return_condition' => 'perbaikan',
        ]);
    }

    public function test_admin_marks_asset_as_damaged_when_returned_condition_is_rusak(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['available_stock' => 4]);
        $borrowing = $this->createBorrowing($asset, ['quantity' => 1, 'status' => 'borrowed']);

        $this->actingAs($admin)
            ->post(route('admin.borrowings.return', $borrowing), ['return_condition' => 'rusak'])
            ->assertRedirect(route('admin.borrowings.index'));

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 5,
            'condition' => 'rusak',
        ]);
        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'returned',
            'return_condition' => 'rusak',
        ]);
    }

    public function test_admin_does_not_increase_stock_above_total_when_returning(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['total_stock' => 5, 'available_stock' => 4]);
        $borrowing = $this->createBorrowing($asset, ['quantity' => 2, 'status' => 'borrowed']);

        $this->actingAs($admin)
            ->from(route('admin.borrowings.index'))
            ->post(route('admin.borrowings.return', $borrowing), ['return_condition' => 'baik'])
            ->assertRedirect(route('admin.borrowings.index'))
            ->assertSessionHasErrors([
                'status' => 'Stok tersedia akan melebihi stok total. Periksa kembali pencatatan stok sebelum menerima pengembalian.',
            ]);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'borrowed',
            'returned_at' => null,
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 4,
            'condition' => 'baik',
        ]);
    }

    public function test_admin_cannot_record_the_same_return_twice(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['total_stock' => 5, 'available_stock' => 3]);
        $borrowing = $this->createBorrowing($asset, ['quantity' => 2, 'status' => 'borrowed']);
        $this->actingAs($admin)
            ->post(route('admin.borrowings.return', $borrowing), ['return_condition' => 'baik'])
            ->assertRedirect(route('admin.borrowings.index'));

        $this->from(route('admin.borrowings.index'))
            ->post(route('admin.borrowings.return', $borrowing), ['return_condition' => 'baik'])
            ->assertRedirect(route('admin.borrowings.index'))
            ->assertSessionHasErrors([
                'status' => 'Hanya transaksi yang sudah diserahkan yang dapat diproses pengembaliannya.',
            ]);

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'returned',
        ]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'available_stock' => 5,
        ]);
    }

    public function test_warga_cannot_manage_or_view_admin_borrowings(): void
    {
        $warga = User::factory()->create(['role' => 'warga']);

        $this->actingAs($warga)
            ->get(route('admin.borrowings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_open_the_complete_digital_borrowing_receipt(): void
    {
        $admin = $this->createAdmin();
        $asset = $this->createAsset(['code' => 'TAKE-AST-701', 'name' => 'Speaker balai warga']);
        $user = User::factory()->create([
            'name' => 'Sari Warga',
            'phone' => '081234567890',
            'address' => 'Blok B 14, RT 02 / RW 05',
        ]);
        $borrowing = $this->createBorrowing($asset, [
            'user_id' => $user->id,
            'booking_code' => 'TAKE-20261123-101',
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.borrowings.receipt', $borrowing))
            ->assertOk()
            ->assertSee('Take · Aset bersama')
            ->assertSee('TAKE-20261123-101')
            ->assertSee('Sari Warga')
            ->assertSee('081234567890')
            ->assertSee('Blok B 14, RT 02 / RW 05')
            ->assertSee('Speaker balai warga')
            ->assertSee('TAKE-AST-701')
            ->assertSee('2 unit')
            ->assertSee('Kegiatan lingkungan')
            ->assertSee('window.print()');
    }

    public function test_guest_is_redirected_to_login_from_admin_borrowings(): void
    {
        $this->get(route('admin.borrowings.index'))
            ->assertRedirect(route('login'));
    }

    private function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createAsset(array $attributes = []): Asset
    {
        $categoryNumber = Category::query()->count() + 1;
        $category = Category::create([
            'name' => "Perlengkapan warga {$categoryNumber}",
            'slug' => "perlengkapan-warga-{$categoryNumber}",
            'icon' => 'box',
        ]);
        $assetNumber = Asset::query()->count() + 701;

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
        $userNumber = User::query()->count() + 1;
        $user = User::factory()->create([
            'name' => "Warga {$userNumber}",
            'role' => 'warga',
        ]);
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
