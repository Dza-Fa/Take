<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class IndonesianHolidayService
{
    /**
     * @return array{holidays: list<array{date: string, name: string}>, source: string, message: ?string}
     */
    public function forYear(int $year): array
    {
        try {
            $holidays = Cache::remember(
                "take:indonesia-public-holidays:{$year}",
                now()->addDay(),
                function () use ($year): array {
                    $response = Http::acceptJson()
                        ->connectTimeout(2)
                        ->timeout(4)
                        ->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/ID")
                        ->throw();
                    $payload = $response->json();

                    if (! is_array($payload) || ! array_is_list($payload)) {
                        throw new UnexpectedValueException('The public holiday API returned an invalid response.');
                    }

                    return collect($payload)
                        ->map(function (mixed $holiday): array {
                            if (! is_array($holiday)
                                || ! is_string($holiday['date'] ?? null)
                                || ! is_string($holiday['localName'] ?? $holiday['name'] ?? null)) {
                                throw new UnexpectedValueException('The public holiday API returned an invalid holiday entry.');
                            }

                            return [
                                'date' => $holiday['date'],
                                'name' => $holiday['localName'] ?? $holiday['name'],
                            ];
                        })
                        ->sortBy('date')
                        ->values()
                        ->all();
                }
            );

            return [
                'holidays' => $holidays,
                'source' => 'nager.date',
                'message' => null,
            ];
        } catch (ConnectionException|RequestException) {
            return $this->fallback();
        }
    }

    /**
     * @return array{holidays: list<array{date: string, name: string}>, source: string, message: string}
     */
    private function fallback(): array
    {
        return [
            'holidays' => [],
            'source' => 'fallback',
            'message' => 'Tanggal libur nasional belum dapat dimuat. Anda tetap dapat memilih tanggal peminjaman.',
        ];
    }
}
