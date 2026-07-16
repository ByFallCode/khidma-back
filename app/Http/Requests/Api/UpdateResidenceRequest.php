<?php

namespace App\Http\Requests\Api;

class UpdateResidenceRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:residences,id'],
            'libelle' => ['required', 'string'],
            'adresse' => ['required', 'string'],
            'telephoneResidence' => ['required', 'string'],
            'responsable.id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'RESIDENCE_LABEL_REQUIRED',
            'adresse.required' => 'RESIDENCE_ADDRESS_REQUIRED',
            'telephoneResidence.required' => 'RESIDENCE_PHONE_REQUIRED',
            'responsable.id.required' => 'RESIDENCE_MANAGER_REQUIRED',
        ];
    }
}
