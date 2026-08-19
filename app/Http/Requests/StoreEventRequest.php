<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 'description' => ['required', 'string', 'max:5000'],
            'event_date' => ['required', 'date'], 'capacity' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
