<?php

namespace App\Http\Requests\Api;

class ChangeOwnPasswordRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:4'],
        ];
    }
}
