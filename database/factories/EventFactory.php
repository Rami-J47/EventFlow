<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->sentence(3), 'description' => fake()->paragraph(), 'event_date' => now()->addWeeks(2), 'capacity' => 50, 'status' => 'active'];
    }
}
