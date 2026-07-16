<?php

namespace App\Http\Requests\Api;

class RoomRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'id' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'exists:rooms,id'],
            'pavillon.id' => ['required', 'integer', 'exists:pavilions,id'],
            'nombrePlace' => ['required', 'integer', 'min:0'],
            'numero' => ['required', 'string'],
            'niveau' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'CHAMBRE_ID_REQUIRED',
            'pavillon.id.required' => 'CHAMBRE_PAVILLON_REQUIRED',
            'nombrePlace.required' => 'CHAMBRE_CAPACITY_REQUIRED',
            'nombrePlace.min' => 'CHAMBRE_CAPACITY_POSITIVE',
            'numero.required' => 'CHAMBRE_NUMBER_REQUIRED',
        ];
    }
}
