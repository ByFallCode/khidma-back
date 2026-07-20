<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class GuestRequest extends ApiRequest
{
    public function rules(): array
    {
        $rules = [
            'prenom' => ['required', 'string', 'max:40'],
            'nom' => ['required', 'string', 'max:20'],
            'telephone' => [
                'required',
                'string',
                'max:15',
                Rule::unique('guests', 'telephone')->ignore($this->route('guest')),
            ],
            'adresse' => ['nullable', 'string', 'max:90'],
            'email' => ['nullable', 'email', 'max:90'],
        ];

        if ($this->isMethod('POST')) {
            $rules['estResponsable'] = ['nullable', 'boolean'];
            $rules['delegation.id'] = ['required', 'integer', 'exists:delegations,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'prenom.required' => 'INVITE_FIRST_NAME_REQUIRED',
            'nom.required' => 'INVITE_LAST_NAME_REQUIRED',
            'telephone.required' => 'INVITE_PHONE_REQUIRED',
            'delegation.id.required' => 'INVITE_DELEGATION_REQUIRED',
        ];
    }
}
