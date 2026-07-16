<?php

namespace App\Http\Requests\Api;

class StorePavilionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string'],
            'niveau' => ['nullable', 'integer', 'min:0'],
            'residence.id' => ['required', 'integer', 'exists:residences,id'],
            'chambres' => ['nullable', 'array'],
            'chambres.*.numero' => ['required', 'string'],
            'chambres.*.nombrePlace' => ['required', 'integer', 'min:0'],
            'chambres.*.niveau' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'PAVILLON_LABEL_REQUIRED',
            'residence.id.required' => 'PAVILLON_RESIDENCE_REQUIRED',
            'chambres.*.numero.required' => 'CHAMBRE_NUMBER_REQUIRED',
            'chambres.*.nombrePlace.required' => 'CHAMBRE_CAPACITY_REQUIRED',
            'chambres.*.nombrePlace.min' => 'CHAMBRE_CAPACITY_POSITIVE',
        ];
    }
}
