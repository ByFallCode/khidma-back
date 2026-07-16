<?php

namespace App\Http\Requests\Api;

class StoreHostRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'utilisateur.prenom' => ['required', 'string'],
            'utilisateur.nom' => ['required', 'string'],
            'utilisateur.telephone' => ['required', 'string', 'unique:users,telephone'],
            'residence.id' => ['required', 'integer', 'exists:residences,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'utilisateur.prenom.required' => 'USER_FIRST_NAME_REQUIRED',
            'utilisateur.nom.required' => 'USER_LAST_NAME_REQUIRED',
            'utilisateur.telephone.required' => 'USER_PHONE_REQUIRED',
            'residence.id.required' => 'ACCUEILLANT_RESIDENCE_REQUIRED',
        ];
    }
}
