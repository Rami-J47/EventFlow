<?php

namespace Tests\Feature;

use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketingWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ticketing.webhook_secret' => self::SECRET]);
    }

    public function test_successful_webhook(): void
    {
        $registration = Registration::factory()->create();
        [$body, $signature] = $this->signedPayload($registration);
        $this->sendWebhook($body, $signature)->assertOk()->assertJson(['duplicate' => false]);
        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'status' => 'confirmed', 'ticket_id' => 'TCK-98765']);
        $this->assertDatabaseHas('webhook_events', ['processing_status' => 'processed']);
    }

    public function test_invalid_webhook_signature(): void
    {
        $registration = Registration::factory()->create();
        [$body] = $this->signedPayload($registration);
        $this->sendWebhook($body, 'invalid')->assertUnauthorized();
        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'status' => 'pending']);
        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $registration = Registration::factory()->create();
        [$body, $signature] = $this->signedPayload($registration);
        $this->sendWebhook($body, $signature)->assertOk()->assertJson(['duplicate' => false]);
        $this->sendWebhook($body, $signature)->assertOk()->assertJson(['duplicate' => true]);
        $this->assertDatabaseCount('webhook_events', 1);
    }

    public function test_demo_sender_uses_the_real_webhook_flow(): void
    {
        $registration = Registration::factory()->create();

        $this->postJson("/api/registrations/{$registration->registration_reference}/demo-confirmation")
            ->assertOk()
            ->assertJson(['duplicate' => false]);

        $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'status' => 'confirmed']);
        $this->assertDatabaseCount('webhook_events', 1);
    }

    private function sendWebhook(string $body, string $signature)
    {
        return $this->call('POST', '/api/webhooks/ticketing', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_WEBHOOK_SIGNATURE' => $signature], $body);
    }

    private function signedPayload(Registration $registration): array
    {
        $body = json_encode(['event' => 'ticket.confirmed', 'registration_reference' => $registration->registration_reference,
            'ticket_id' => 'TCK-98765', 'status' => 'confirmed'], JSON_THROW_ON_ERROR);

        return [$body, hash_hmac('sha256', $body, self::SECRET)];
    }
}
