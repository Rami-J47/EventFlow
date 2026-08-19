<?php

namespace Database\Seeders;

use App\Models\Event;
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

        Event::query()->create(['name' => 'Beirut Product Meetup', 'description' => 'An evening of practical product and engineering talks.', 'event_date' => now()->addWeeks(2)->setTime(18, 30), 'capacity' => 40, 'status' => 'active']);
        Event::query()->create(['name' => 'Full-Stack Workshop', 'description' => 'A focused workshop on building reliable web applications.', 'event_date' => now()->addMonth()->setTime(10, 0), 'capacity' => 24, 'status' => 'active']);
    }
}
