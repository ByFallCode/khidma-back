<?php

namespace App\Http\Requests\Api;

class StoreResidenceRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string'],
            'adresse' => ['required', 'string'],
            'telephoneResidence' => ['required', 'string'],
            'prenom' => ['required', 'string'],
            'nom' => ['required', 'string'],
            'telephone' => ['required', 'string', 'unique:users,telephone'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'image' => ['nullable', 'file', 'image', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'RESIDENCE_LABEL_REQUIRED',
            'adresse.required' => 'RESIDENCE_ADDRESS_REQUIRED',
            'telephoneResidence.required' => 'RESIDENCE_PHONE_REQUIRED',
            'nom.required' => 'RESIDENCE_MANAGER_LAST_NAME_REQUIRED',
            'prenom.required' => 'RESIDENCE_MANAGER_FIRST_NAME_REQUIRED',
            'telephone.required' => 'RESIDENCE_MANAGER_PHONE_REQUIRED',
            'telephone.unique' => 'USER_PHONE_ALREADY_USED',
        ];
    }
}
