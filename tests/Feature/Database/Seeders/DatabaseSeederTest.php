<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Borrowing;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_database_seeder_creates_the_requested_foundation_data(): void
    {
        $this->travelTo('2026-11-23 12:00:00');
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('categories', 4);
        $this->assertDatabaseCount('assets', 10);
        $this->assertDatabaseCount('borrowings', 4);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@take.local',
            'role' => 'admin',
        ]);

        foreach ([
            'admin@take.local',
            'sari@take.local',
            'raka@take.local',
            'dedi@take.local',
        ] as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertTrue(Hash::check('password123', $user->password));
        }

        $this->assertDatabaseHas('borrowings', [
            'booking_code' => 'TAKE-20261123-003',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('borrowings', [
            'booking_code' => 'TAKE-20261123-004',
            'status' => 'returned',
            'return_condition' => 'perbaikan',
        ]);

        $overdueBorrowing = Borrowing::query()
            ->where('booking_code', 'TAKE-20261123-002')
            ->firstOrFail();

        $this->assertTrue($overdueBorrowing->isOverdue());
    }
}
