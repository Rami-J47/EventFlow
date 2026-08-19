<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\WebhookEvent;
use DomainException;
use Illuminate\Support\Facades\DB;

class TicketingWebhookService
{
    public function process(array $payload): bool
    {
        $eventKey = hash('sha256', implode('|', [$payload['event'], $payload['registration_reference'], $payload['ticket_id'], $payload['status']]));

        return DB::transaction(function () use ($eventKey, $payload) {
            $event = WebhookEvent::query()->firstOrCreate(['event_key' => $eventKey], [
                'event_type' => $payload['event'], 'registration_reference' => $payload['registration_reference'],
                'ticket_id' => $payload['ticket_id'], 'payload' => $payload, 'processing_status' => 'received',
            ]);
            if (! $event->wasRecentlyCreated) {
                return false;
            }
            $registration = Registration::query()->where('registration_reference', $payload['registration_reference'])->lockForUpdate()->firstOrFail();
            if ($registration->ticket_id && $registration->ticket_id !== $payload['ticket_id']) {
                throw new DomainException('The registration already has a different ticket.');
            }
            $registration->update(['status' => 'confirmed', 'ticket_id' => $payload['ticket_id']]);
            $event->update(['processing_status' => 'processed', 'processed_at' => now()]);

            return true;
        });
    }
}
