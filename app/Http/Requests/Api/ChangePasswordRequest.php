<?php

namespace App\Http\Requests\Api;

class ChangePasswordRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['password' => ['required', 'string', 'min:4']];
    }
}
