<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(S1SampleSeeder::class);
        // S3 library fixtures for local development (labelled, non-production).
        $this->call(S3SampleSeeder::class);
        // S5 payment fixtures (labelled TEST values, never real prices).
        $this->call(S5PaymentFixtureSeeder::class);
        // S7 placement fixture (labelled FIXTURE rows, never real exam).
        $this->call(S7PlacementFixtureSeeder::class);
    }
}
