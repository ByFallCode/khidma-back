<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdateOwnProfileRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'telephone' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users', 'telephone')->ignore($this->user()?->id),
            ],
            'whatsapp' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'prenom.required' => 'USER_FIRST_NAME_REQUIRED',
            'nom.required' => 'USER_LAST_NAME_REQUIRED',
            'telephone.required' => 'USER_PHONE_REQUIRED',
            'telephone.unique' => 'USER_PHONE_ALREADY_USED',
        ];
    }
}
