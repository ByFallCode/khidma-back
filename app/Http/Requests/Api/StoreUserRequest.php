<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreUserRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'prenom' => ['required', 'string'],
            'nom' => ['required', 'string'],
            'telephone' => ['required', 'string', 'max:30', 'unique:users,telephone'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:4'],
            'accountType' => ['nullable', Rule::in(['ADMIN', 'KHIDMA_AGENT'])],
        ];
    }

    public function messages(): array
    {
        return [
            'prenom.required' => 'USER_FIRST_NAME_REQUIRED',
            'nom.required' => 'USER_LAST_NAME_REQUIRED',
            'telephone.required' => 'USER_PHONE_REQUIRED',
        ];
    }
}
