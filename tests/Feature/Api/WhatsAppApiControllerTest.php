<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppApiControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_preview_new_request_with_normalized_admin_phone_and_booking_details(): void
    {
        Http::preventStrayRequests();
        config(['services.whatsapp.admin_phone' => '(0812) 3456-7890']);
        $user = User::factory()->create([
            'name' => 'Sari Warga',
            'phone' => '081234567890',
            'address' => 'Blok B 14, RT 02 / RW 05',
            'role' => 'warga',
        ]);
        $borrowing = $this->createBorrowing($user);

        $response = $this->actingAs($user)
            ->getJson(route('api.borrowings.whatsapp.preview', [
                'bookingCode' => $borrowing->booking_code,
                'type' => 'new_request',
            ]));

        $response->assertOk()
            ->assertJsonPath('data.phone', '6281234567890');

        $message = $response->json('data.text');
        $this->assertIsString($message);
        $this->assertStringContainsString('Sari Warga', $message);
        $this->assertStringContainsString('Blok B 14, RT 02 / RW 05', $message);
        $this->assertStringContainsString('Rapat warga', $message);
        $this->assertSame(
            'https://wa.me/6281234567890?text='.rawurlencode($message),
            $response->json('data.wa_url'),
        );
    }

    public function test_admin_can_preview_status_message_to_borrower_including_admin_note(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['phone' => '+62 812-3456-7890']);
        $borrowing = $this->createBorrowing($user, [
            'status' => 'rejected',
            'admin_note' => 'Jadwal berbenturan dengan kegiatan warga.',
        ]);

        $this->actingAs($admin)
            ->getJson(route('api.borrowings.whatsapp.preview', [
                'bookingCode' => $borrowing->booking_code,
                'type' => 'status',
            ]))
            ->assertOk()
            ->assertJsonPath('data.phone', '6281234567890')
            ->assertJsonPath('data.text', fn (string $message): bool => str_contains($message, 'ditolak')
                && str_contains($message, 'Catatan pengurus: Jadwal berbenturan dengan kegiatan warga.'));
    }

    public function test_admin_can_preview_overdue_reminder_for_active_borrowing(): void
    {
        $this->travelTo('2026-11-25 09:00:00');
        Http::preventStrayRequests();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['phone' => '081234567890']);
        $borrowing = $this->createBorrowing($user, [
            'status' => 'borrowed',
            'return_date' => '2026-11-24',
        ]);

        $this->actingAs($admin)
            ->getJson(route('api.borrowings.whatsapp.preview', [
                'bookingCode' => $borrowing->booking_code,
                'type' => 'reminder',
            ]))
            ->assertOk()
            ->assertJsonPath('data.text', fn (string $message): bool => str_contains($message, 'sudah melewati batas pengembalian')
                && str_contains($message, 'Batas pengembalian: 24/11/2026'));
    }

    public function test_guest_receives_401_for_whatsapp_preview(): void
    {
        $borrowing = $this->createBorrowing();

        $this->getJson(route('api.borrowings.whatsapp.preview', [
            'bookingCode' => $borrowing->booking_code,
            'type' => 'new_request',
        ]))->assertUnauthorized();
    }

    public function test_warga_cannot_preview_another_users_whatsapp_message(): void
    {
        $owner = User::factory()->create(['role' => 'warga']);
        $anotherUser = User::factory()->create(['role' => 'warga']);
        $borrowing = $this->createBorrowing($owner);

        $this->actingAs($anotherUser)
            ->getJson(route('api.borrowings.whatsapp.preview', [
                'bookingCode' => $borrowing->booking_code,
                'type' => 'new_request',
            ]))
            ->assertNotFound();
    }

    public function test_whatsapp_preview_returns_422_when_recipient_phone_is_invalid(): void
    {
        config(['services.whatsapp.admin_phone' => '021-555-123']);
        $user = User::factory()->create(['role' => 'warga']);
        $borrowing = $this->createBorrowing($user);

        $this->actingAs($user)
            ->getJson(route('api.borrowings.whatsapp.preview', [
                'bookingCode' => $borrowing->booking_code,
                'type' => 'new_request',
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Nomor WhatsApp tidak valid atau belum diisi.');
    }

    public function test_admin_send_uses_configured_gateway_with_bearer_token(): void
    {
        $this->travelTo('2026-11-25 09:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['phone' => '081234567890']);
        $borrowing = $this->createBorrowing($user, [
            'status' => 'borrowed',
            'return_date' => '2026-11-24',
        ]);
        config([
            'services.whatsapp.api_url' => 'https://gateway.test/messages',
            'services.whatsapp.api_token' => 'demo-token',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://gateway.test/messages' => Http::response(['accepted' => true], 202),
        ]);

        $this->actingAs($admin)
            ->postJson(route('api.borrowings.whatsapp.send', $borrowing->booking_code), [
                'type' => 'reminder',
            ])
            ->assertOk()
            ->assertJsonPath('data.sent', true)
            ->assertJsonPath('data.delivery', 'gateway')
            ->assertSee('berhasil dikirim');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://gateway.test/messages'
            && $request->hasHeader('Authorization', 'Bearer demo-token')
            && $request['phone'] === '6281234567890'
            && str_contains($request['message'], 'sudah melewati batas pengembalian'));
    }

    public function test_gateway_failure_returns_a_wa_link_fallback(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['phone' => '081234567890']);
        $borrowing = $this->createBorrowing($user, ['status' => 'borrowed']);
        config([
            'services.whatsapp.api_url' => 'https://gateway.test/messages',
            'services.whatsapp.api_token' => 'demo-token',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://gateway.test/messages' => Http::response([], 503),
        ]);

        $this->actingAs($admin)
            ->postJson(route('api.borrowings.whatsapp.send', $borrowing->booking_code), [
                'type' => 'status',
            ])
            ->assertOk()
            ->assertJsonPath('data.sent', false)
            ->assertJsonPath('data.delivery', 'wa_link')
            ->assertJsonPath('data.wa_url', fn (string $url): bool => str_starts_with($url, 'https://wa.me/6281234567890?text='));
    }

    public function test_missing_gateway_configuration_returns_wa_link_without_sending(): void
    {
        config(['services.whatsapp.admin_phone' => '081234567890']);
        $user = User::factory()->create(['role' => 'warga']);
        $borrowing = $this->createBorrowing($user);
        Http::preventStrayRequests();

        $this->actingAs($user)
            ->postJson(route('api.borrowings.whatsapp.send', $borrowing->booking_code), [
                'type' => 'new_request',
            ])
            ->assertOk()
            ->assertJsonPath('data.sent', false)
            ->assertJsonPath('data.delivery', 'wa_link')
            ->assertJsonPath('data.phone', '6281234567890');
    }

    public function test_borrowing_pages_render_role_appropriate_whatsapp_actions(): void
    {
        $user = User::factory()->create(['role' => 'warga']);
        $pending = $this->createBorrowing($user);
        $this->createBorrowing($user, [
            'booking_code' => 'TAKE-20261125-002',
            'status' => 'borrowed',
        ]);

        $this->actingAs($user)
            ->get(route('my-borrowings.index'))
            ->assertOk()
            ->assertSee('Kirim ringkasan ke pengurus via WhatsApp');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.borrowings.index'))
            ->assertOk()
            ->assertSee('Kirim konfirmasi status via WhatsApp')
            ->assertSee('Kirim pengingat pengembalian');
    }

    private function createBorrowing(?User $user = null, array $attributes = []): Borrowing
    {
        $user ??= User::factory()->create([
            'phone' => '081234567890',
            'address' => 'Blok B 14, RT 02 / RW 05',
            'role' => 'warga',
        ]);
        $categoryNumber = Category::query()->count() + 1;
        $category = Category::create([
            'name' => 'Perlengkapan warga '.$categoryNumber,
            'slug' => 'perlengkapan-warga-'.$categoryNumber,
            'icon' => 'box',
        ]);
        $asset = Asset::create([
            'category_id' => $category->id,
            'code' => 'TAKE-AST-'.str_pad((string) (Asset::query()->count() + 801), 3, '0', STR_PAD_LEFT),
            'name' => 'Speaker rapat',
            'total_stock' => 5,
            'available_stock' => 3,
            'condition' => 'baik',
        ]);
        $borrowingNumber = Borrowing::query()->count() + 1;

        return Borrowing::create(array_merge([
            'booking_code' => sprintf('TAKE-20261125-%03d', $borrowingNumber),
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'quantity' => 2,
            'borrow_date' => '2026-11-25',
            'return_date' => '2026-11-27',
            'purpose' => 'Rapat warga',
            'status' => 'pending',
        ], $attributes));
    }
}
