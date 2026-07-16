<?php

namespace App\Http\Requests\Api;

class EventRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer', 'exists:events,id'],
            'libelle' => ['required', 'string'],
            'dateDebut' => ['nullable', 'date_format:Y-m-d'],
            'dateFin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateDebut'],
        ];
    }

    public function messages(): array
    {
        return ['libelle.required' => 'EVENEMENT_NAME_REQUIRED'];
    }
}
