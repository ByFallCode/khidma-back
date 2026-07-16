<?php

namespace App\Http\Requests\Api;

class DelegationRequest extends ApiRequest
{
    public function rules(): array
    {
        $guestRules = [
            'prenom' => ['required', 'string', 'max:40'],
            'nom' => ['required', 'string', 'max:20'],
            'telephone' => ['required', 'string', 'max:15'],
            'adresse' => ['nullable', 'string', 'max:90'],
            'email' => ['nullable', 'email', 'max:90'],
        ];

        $rules = [
            'id' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer', 'exists:delegations,id'],
            'nom' => ['required', 'string'],
            'nombre' => ['required', 'integer', 'min:1', 'max:50'],
            'chef' => ['required', 'array'],
            ...collect($guestRules)->mapWithKeys(fn ($rules, $field) => ["chef.{$field}" => $rules])->all(),
            'invites' => ['present', 'array'],
            ...collect($guestRules)->mapWithKeys(fn ($rules, $field) => ["invites.*.{$field}" => $rules])->all(),
        ];

        if ($this->isMethod('POST')) {
            $rules['chef.telephone'][] = 'unique:guests,telephone';
            $rules['invites.*.telephone'][] = 'distinct';
            $rules['invites.*.telephone'][] = 'unique:guests,telephone';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'DELEGATION_NAME_REQUIRED',
            'nombre.required' => 'DELEGATION_SIZE_REQUIRED',
            'chef.required' => 'DELEGATION_LEADER_REQUIRED',
            'chef.prenom.required' => 'INVITE_FIRST_NAME_REQUIRED',
            'chef.nom.required' => 'INVITE_LAST_NAME_REQUIRED',
            'chef.telephone.required' => 'INVITE_PHONE_REQUIRED',
            'invites.*.prenom.required' => 'INVITE_FIRST_NAME_REQUIRED',
            'invites.*.nom.required' => 'INVITE_LAST_NAME_REQUIRED',
            'invites.*.telephone.required' => 'INVITE_PHONE_REQUIRED',
        ];
    }
}
