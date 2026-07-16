<?php

namespace App\Http\Requests\Api;

class LoginRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['username' => ['required', 'string'], 'password' => ['required', 'string']];
    }
}
