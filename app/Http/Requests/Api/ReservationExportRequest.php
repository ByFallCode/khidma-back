<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class ReservationExportRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:-1'],
            'event' => ['required', 'integer', 'min:-1'],
            'presence' => ['nullable', 'integer', Rule::in([-1, 0, 1])],
            'locale' => ['nullable', 'string', Rule::in(['fr', 'ar'])],
        ];
    }
}
