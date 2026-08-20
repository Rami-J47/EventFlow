<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_crud_endpoints_required_by_assignment(): void
    {
        $payload = ['name' => 'Developer Conference', 'description' => 'A practical developer conference.',
            'event_date' => now()->addWeek()->toIso8601String(), 'capacity' => 25, 'status' => 'active'];
        $created = $this->postJson('/api/events', $payload)->assertCreated()->assertJsonPath('data.name', 'Developer Conference');
        $id = $created->json('data.id');
        $this->getJson('/api/events')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/events/{$id}")->assertOk()->assertJsonPath('data.capacity', 25);
        $this->patchJson("/api/events/{$id}", ['capacity' => 30])->assertOk()->assertJsonPath('data.capacity', 30);
    }

    public function test_unavailable_events_are_not_listed(): void
    {
        Event::factory()->create(['status' => 'inactive']);
        Event::factory()->create(['event_date' => now()->subDay()]);
        $this->getJson('/api/events')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_event_capacity_cannot_be_reduced_below_existing_registrations(): void
    {
        $event = Event::factory()->create(['capacity' => 3]);
        $event->registrations()->createMany([
            ['registration_reference' => 'EVT-AAAA1111', 'first_name' => 'Rami', 'last_name' => 'Jawhary', 'email' => 'rami@example.com', 'phone' => '+961 70 111 111'],
            ['registration_reference' => 'EVT-BBBB2222', 'first_name' => 'Maya', 'last_name' => 'Haddad', 'email' => 'maya@example.com', 'phone' => '+961 70 222 222'],
        ]);

        $this->patchJson("/api/events/{$event->id}", ['capacity' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('capacity');

        $this->assertDatabaseHas('events', ['id' => $event->id, 'capacity' => 3]);
    }
}
