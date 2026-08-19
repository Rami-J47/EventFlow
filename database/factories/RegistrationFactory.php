<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(), 'registration_reference' => 'EVT-'.Str::upper(Str::random(8)),
            'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(), 'phone' => fake()->phoneNumber(), 'status' => 'pending', 'ticket_id' => null,
        ];
    }
}
