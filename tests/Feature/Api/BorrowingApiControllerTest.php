<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BorrowingApiControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_borrowing_status_returns_only_the_public_summary_and_overdue_flag(): void
    {
        $this->travelTo('2026-11-24 09:00:00');
        $user = User::factory()->create([
            'role' => 'warga',
            'name' => 'Sari Warga',
            'email' => 'sari@example.test',
        ]);
        $category = Category::create([
            'name' => 'Perlengkapan warga',
            'slug' => 'perlengkapan-warga',
            'icon' => 'box',
        ]);
        $asset = Asset::create([
            'category_id' => $category->id,
            'code' => 'TAKE-AST-701',
            'name' => 'Speaker rapat',
            'total_stock' => 5,
            'available_stock' => 3,
            'condition' => 'baik',
        ]);
        $borrowing = Borrowing::create([
            'booking_code' => 'TAKE-20261123-001',
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'quantity' => 2,
            'borrow_date' => '2026-11-22',
            'return_date' => '2026-11-23',
            'purpose' => 'Rapat warga rahasia',
            'status' => 'borrowed',
            'admin_note' => 'Catatan internal admin',
        ]);

        $this->getJson(route('api.borrowings.show', $borrowing->booking_code))
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Status peminjaman berhasil dimuat.',
                'data' => [
                    'booking_code' => 'TAKE-20261123-001',
                    'asset_name' => 'Speaker rapat',
                    'quantity' => 2,
                    'borrow_date' => '2026-11-22',
                    'return_date' => '2026-11-23',
                    'status' => 'borrowed',
                    'is_overdue' => true,
                ],
            ])
            ->assertDontSee('sari@example.test')
            ->assertDontSee('Rapat warga rahasia')
            ->assertDontSee('Catatan internal admin');
    }

    public function test_returns_422_when_borrowing_code_format_is_invalid(): void
    {
        $this->getJson(route('api.borrowings.show', 'NOT-A-CODE'))
            ->assertUnprocessable()
            ->assertExactJson([
                'success' => false,
                'message' => 'Format kode peminjaman tidak valid.',
                'data' => null,
            ]);
    }

    public function test_returns_404_when_borrowing_code_does_not_exist(): void
    {
        $this->getJson(route('api.borrowings.show', 'TAKE-20261123-999'))
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'message' => 'Peminjaman tidak ditemukan.',
                'data' => null,
            ]);
    }
}
