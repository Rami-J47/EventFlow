<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    public function create(Event $event, array $data): Registration
    {
        return DB::transaction(function () use ($event, $data) {
            // Serializing registrations for one event prevents two requests from taking the final seat.
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
            if ($lockedEvent->status !== 'active' || $lockedEvent->event_date->isPast()) {
                throw new DomainException('This event is not available for registration.');
            }
            if ($lockedEvent->registrations()->count() >= $lockedEvent->capacity) {
                throw new DomainException('This event has reached its capacity.');
            }

            return $lockedEvent->registrations()->create([...$data, 'registration_reference' => $this->uniqueReference(), 'status' => 'pending']);
        });
    }

    private function uniqueReference(): string
    {
        do {
            $reference = 'EVT-'.Str::upper(Str::random(8));
        } while (Registration::query()->where('registration_reference', $reference)->exists());

        return $reference;
    }
}
