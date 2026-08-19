<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $count = $this->relationLoaded('registrations') ? $this->registrations->count() : ($this->registrations_count ?? null);

        return [
            'id' => $this->id, 'name' => $this->name, 'description' => $this->description,
            'event_date' => $this->event_date->toIso8601String(), 'capacity' => $this->capacity,
            'remaining_capacity' => $count === null ? null : max(0, $this->capacity - $count), 'status' => $this->status,
        ];
    }
}
