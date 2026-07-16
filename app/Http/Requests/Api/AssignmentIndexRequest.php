<?php

namespace App\Http\Requests\Api;

class AssignmentIndexRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'residenceId' => ['required', 'integer', 'exists:residences,id'],
            'page' => ['nullable', 'integer', 'min:0'],
            'size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string'],
        ];
    }
}
