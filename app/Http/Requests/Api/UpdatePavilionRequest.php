<?php

namespace App\Http\Requests\Api;

class UpdatePavilionRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['libelle' => ['required', 'string'], 'niveau' => ['required', 'integer', 'min:0']];
    }

    public function messages(): array
    {
        return ['libelle.required' => 'PAVILLON_LABEL_REQUIRED'];
    }
}
