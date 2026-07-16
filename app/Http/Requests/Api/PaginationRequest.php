<?php

namespace App\Http\Requests\Api;

class PaginationRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:0'],
            'size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string'],
            'residence' => ['nullable', 'integer'],
            'accountType' => ['nullable', 'string'],
        ];
    }
}
