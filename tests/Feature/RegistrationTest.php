<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = ['first_name' => 'Salma', 'last_name' => 'Haddad', 'email' => 'salma@example.com', 'phone' => '+961 70 123 456'];

    public function test_successful_registration(): void
    {
        $event = Event::factory()->create();
        $response = $this->postJson("/api/events/{$event->id}/registrations", $this->validPayload)
            ->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.event.id', $event->id);
        $reference = $response->json('data.registration_reference');
        $this->assertMatchesRegularExpression('/^EVT-[A-Z0-9]{8}$/', $reference);
        $this->assertDatabaseHas('registrations', ['registration_reference' => $reference, 'status' => 'pending']);
    }

    public function test_registration_validation_failure(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/events/{$event->id}/registrations", ['email' => 'invalid'])->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'phone']);
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_event_capacity_cannot_be_exceeded(): void
    {
        $event = Event::factory()->create(['capacity' => 1]);
        Registration::factory()->create(['event_id' => $event->id]);
        $this->postJson("/api/events/{$event->id}/registrations", $this->validPayload)->assertConflict()
            ->assertJsonPath('message', 'This event has reached its capacity.');
        $this->assertDatabaseCount('registrations', 1);
    }
}
