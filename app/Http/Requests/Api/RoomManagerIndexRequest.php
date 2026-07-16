<?php

namespace App\Http\Requests\Api;

class RoomManagerIndexRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:0'],
            'size' => ['nullable', 'integer', 'min:1', 'max:200'],
            'search' => ['nullable', 'string'],
            'residence' => ['nullable', 'integer'],
        ];
    }
}
