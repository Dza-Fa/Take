<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BorrowingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_submission_creates_pending_borrowing_without_changing_stock_and_generates_unique_codes(): void
    {
        $this->travelTo('2026-11-23 09:00:00');
        $user = User::factory()->create(['role' => 'warga']);
        $anotherUser = User::factory()->create(['role' => 'admin']);
        $asset = $this->createAsset(['total_stock' => 4, 'available_stock' => 4]);
        $anotherAsset = $this->createAsset();

        $this->actingAs($user)
            ->post(route('borrowings.store', $asset), $this->validPayload([
                'user_id' => $anotherUser->id,
                'booking_code' => 'TAKE-20261123-999',
                'status' => 'approved',
            ]))
            ->assertRedirect(route('my-borrowings.index'));

        $borrowing = Borrowing::query()->where('asset_id', $asset->id)->firstOrFail();

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'booking_code' => 'TAKE-20261123-001',
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);
        $this->assertSame(4, $asset->fresh()->available_stock);

        $this->post(route('borrowings.store', $anotherAsset), $this->validPayload())
            ->assertRedirect(route('my-borrowings.index'));

        $this->assertDatabaseHas('borrowings', [
            'booking_code' => 'TAKE-20261123-002',
            'user_id' => $user->id,
            'asset_id' => $anotherAsset->id,
            'status' => 'pending',
        ]);
    }

    public function test_submission_rejects_a_quantity_above_available_stock(): void
    {
        $asset = $this->createAsset(['available_stock' => 2]);

        $this->actingAs(User::factory()->create(['role' => 'warga']))
            ->from(route('catalog.show', $asset))
            ->post(route('borrowings.store', $asset), $this->validPayload(['quantity' => 3]))
            ->assertRedirect(route('catalog.show', $asset))
            ->assertSessionHasErrors([
                'quantity' => 'Jumlah pinjaman melebihi stok yang tersedia.',
            ]);

        $this->assertDatabaseCount('borrowings', 0);
        $this->assertSame(2, $asset->fresh()->available_stock);
    }

    public function test_submission_rejects_an_asset_in_rusak_condition(): void
    {
        $asset = $this->createAsset(['condition' => 'rusak']);

        $this->actingAs(User::factory()->create(['role' => 'warga']))
            ->from(route('catalog.show', $asset))
            ->post(route('borrowings.store', $asset), $this->validPayload())
            ->assertRedirect(route('catalog.show', $asset))
            ->assertSessionHasErrors([
                'quantity' => 'Aset dalam kondisi rusak dan tidak dapat dipinjam.',
            ]);

        $this->assertDatabaseCount('borrowings', 0);
        $this->assertSame(5, $asset->fresh()->available_stock);
    }

    public function test_submission_rejects_quantity_below_one(): void
    {
        $asset = $this->createAsset();

        $this->actingAs(User::factory()->create(['role' => 'warga']))
            ->from(route('catalog.show', $asset))
            ->post(route('borrowings.store', $asset), $this->validPayload(['quantity' => 0]))
            ->assertSessionHasErrors([
                'quantity' => 'Jumlah pinjaman minimal 1 unit.',
            ]);

        $this->assertDatabaseCount('borrowings', 0);
    }

    public function test_submission_rejects_a_borrow_date_before_today(): void
    {
        $this->travelTo('2026-11-23 09:00:00');
        $asset = $this->createAsset();

        $this->actingAs(User::factory()->create(['role' => 'warga']))
            ->from(route('catalog.show', $asset))
            ->post(route('borrowings.store', $asset), $this->validPayload([
                'borrow_date' => '2026-11-22',
                'return_date' => '2026-11-24',
            ]))
            ->assertSessionHasErrors([
                'borrow_date' => 'Tanggal pinjam tidak boleh sebelum hari ini.',
            ]);

        $this->assertDatabaseCount('borrowings', 0);
    }

    public function test_submission_rejects_a_return_date_before_the_borrow_date(): void
    {
        $this->travelTo('2026-11-23 09:00:00');
        $asset = $this->createAsset();

        $this->actingAs(User::factory()->create(['role' => 'warga']))
            ->from(route('catalog.show', $asset))
            ->post(route('borrowings.store', $asset), $this->validPayload([
                'borrow_date' => '2026-11-25',
                'return_date' => '2026-11-24',
            ]))
            ->assertSessionHasErrors([
                'return_date' => 'Tanggal kembali harus sama atau setelah tanggal pinjam.',
            ]);

        $this->assertDatabaseCount('borrowings', 0);
    }

    public function test_submission_requires_a_purpose(): void
    {
        $asset = $this->createAsset();
        $payload = $this->validPayload();
        unset($payload['purpose']);

        $this->actingAs(User::factory()->create(['role' => 'warga']))
            ->from(route('catalog.show', $asset))
            ->post(route('borrowings.store', $asset), $payload)
            ->assertSessionHasErrors([
                'purpose' => 'Keperluan acara wajib diisi.',
            ]);

        $this->assertDatabaseCount('borrowings', 0);
    }

    public function test_my_borrowings_only_shows_the_current_users_records_and_overdue_warning(): void
    {
        $this->travelTo('2026-11-23 09:00:00');
        $user = User::factory()->create(['role' => 'warga']);
        $anotherUser = User::factory()->create(['role' => 'warga']);
        $asset = $this->createAsset();
        $this->createBorrowing($user, $asset, 'TAKE-20261123-101', 'borrowed', '2026-11-22');
        $this->createBorrowing($anotherUser, $asset, 'TAKE-20261123-102', 'borrowed', '2026-11-22');

        $this->actingAs($user)
            ->get(route('my-borrowings.index'))
            ->assertOk()
            ->assertSee('TAKE-20261123-101')
            ->assertSee('Terlambat dikembalikan')
            ->assertDontSee('TAKE-20261123-102');
    }

    public function test_owner_can_open_and_print_the_digital_borrowing_receipt(): void
    {
        $user = User::factory()->create(['role' => 'warga']);
        $asset = $this->createAsset();
        $borrowing = $this->createBorrowing($user, $asset, 'TAKE-20261123-103');

        $this->actingAs($user)
            ->get(route('my-borrowings.receipt', $borrowing))
            ->assertOk()
            ->assertSee('Kartu Bukti Pinjam')
            ->assertSee($borrowing->booking_code)
            ->assertSee('window.print()')
            ->assertSee('Cetak kartu');
    }

    public function test_user_cannot_open_another_users_receipt(): void
    {
        $owner = User::factory()->create(['role' => 'warga']);
        $anotherUser = User::factory()->create(['role' => 'warga']);
        $asset = $this->createAsset();
        $borrowing = $this->createBorrowing($owner, $asset, 'TAKE-20261123-104');

        $this->actingAs($anotherUser)
            ->get(route('my-borrowings.receipt', $borrowing))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login_before_submitting_a_borrowing(): void
    {
        $asset = $this->createAsset();

        $this->post(route('borrowings.store', $asset), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('borrowings', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'quantity' => 2,
            'borrow_date' => '2026-11-23',
            'return_date' => '2026-11-24',
            'purpose' => 'Rapat warga',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAsset(array $attributes = []): Asset
    {
        $sequence = Category::query()->count() + 1;
        $category = Category::create([
            'name' => "Perlengkapan warga {$sequence}",
            'slug' => "perlengkapan-warga-{$sequence}",
            'icon' => 'box',
        ]);
        $assetSequence = Asset::query()->count() + 1;

        return Asset::create(array_merge([
            'category_id' => $category->id,
            'code' => sprintf('TAKE-AST-%03d', $assetSequence),
            'name' => 'Aset warga',
            'total_stock' => 5,
            'available_stock' => 5,
            'condition' => 'baik',
        ], $attributes));
    }

    private function createBorrowing(
        User $user,
        Asset $asset,
        string $bookingCode,
        string $status = 'borrowed',
        string $returnDate = '2026-11-24'
    ): Borrowing {
        return Borrowing::create([
            'booking_code' => $bookingCode,
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'borrow_date' => '2026-11-20',
            'return_date' => $returnDate,
            'purpose' => 'Kegiatan warga',
            'status' => $status,
        ]);
    }
}
