<?php

namespace App\Http\Requests\Api;

use App\Models\Host;
use Illuminate\Validation\Rule;

class UpdateHostRequest extends StoreHostRequest
{
    public function rules(): array
    {
        $host = Host::find($this->integer('id'));

        return [
            'id' => ['required', 'integer', 'exists:hosts,id'],
            'utilisateur.prenom' => ['required', 'string'],
            'utilisateur.nom' => ['required', 'string'],
            'utilisateur.telephone' => ['required', 'string', Rule::unique('users', 'telephone')->ignore($host?->user_id)],
            'residence.id' => ['required', 'integer', 'exists:residences,id'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), ['id.required' => 'ACCUEILLANT_ID_REQUIRED']);
    }
}
