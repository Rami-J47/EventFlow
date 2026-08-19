<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'registration_reference' => $this->registration_reference, 'status' => $this->status,
            'ticket_id' => $this->ticket_id, 'event' => new EventResource($this->whenLoaded('event')),
        ];
    }
}
