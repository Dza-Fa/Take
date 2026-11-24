<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HolidayApiControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_holiday_endpoint_returns_local_names_and_caches_successful_api_data(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'date.nager.at/api/v3/PublicHolidays/2026/ID' => Http::response([
                [
                    'date' => '2026-08-17',
                    'localName' => 'Hari Kemerdekaan Republik Indonesia',
                    'name' => 'Independence Day',
                ],
                [
                    'date' => '2026-01-01',
                    'localName' => 'Tahun Baru',
                    'name' => "New Year's Day",
                ],
            ]),
        ]);

        $this->getJson(route('api.holidays.index', ['year' => 2026]))
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Tanggal libur nasional berhasil dimuat.',
                'data' => [
                    'year' => 2026,
                    'holidays' => [
                        ['date' => '2026-01-01', 'name' => 'Tahun Baru'],
                        ['date' => '2026-08-17', 'name' => 'Hari Kemerdekaan Republik Indonesia'],
                    ],
                    'source' => 'nager.date',
                    'message' => null,
                ],
            ]);

        $this->getJson(route('api.holidays.index', ['year' => 2026]))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_holiday_endpoint_returns_safe_fallback_when_public_api_is_unavailable(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'date.nager.at/api/v3/PublicHolidays/2026/ID' => Http::failedConnection(),
        ]);

        $this->getJson(route('api.holidays.index', ['year' => 2026]))
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Tanggal libur nasional belum dapat dimuat. Anda tetap dapat memilih tanggal peminjaman.',
                'data' => [
                    'year' => 2026,
                    'holidays' => [],
                    'source' => 'fallback',
                    'message' => 'Tanggal libur nasional belum dapat dimuat. Anda tetap dapat memilih tanggal peminjaman.',
                ],
            ]);

        Http::assertSentCount(1);
    }

    public function test_returns_422_when_holiday_year_is_out_of_range(): void
    {
        Http::preventStrayRequests();

        $this->getJson(route('api.holidays.index', ['year' => 2101]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Parameter tahun tidak valid.')
            ->assertJsonStructure(['data' => ['errors' => ['year']]]);
    }
}
