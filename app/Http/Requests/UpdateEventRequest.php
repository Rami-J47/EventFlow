<?php

namespace App\Http\Requests;

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
            'event_date' => ['sometimes', 'required', 'date'], 'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
        ];
    }
}
