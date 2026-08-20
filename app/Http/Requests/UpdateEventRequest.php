<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'], 'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'event_date' => ['sometimes', 'required', 'date'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', function (string $attribute, mixed $value, $fail) {
                $event = $this->route('event');
                if ($event instanceof Event && $event->registrations()->count() > (int) $value) {
                    $fail('The capacity cannot be lower than the current registration count.');
                }
            }],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
        ];
    }
}
