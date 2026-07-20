<?php

namespace App\Http\Requests\Api;

use App\Models\Residence;
use Illuminate\Validation\Rule;

class UpdateResidenceRequest extends ApiRequest
{
    public function rules(): array
    {
        $managerId = Residence::find($this->integer('id'))?->responsable_id;

        return [
            'id' => ['required', 'integer', 'exists:residences,id'],
            'libelle' => ['required', 'string'],
            'adresse' => ['required', 'string'],
            'telephoneResidence' => ['required', 'string'],
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'telephone' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users', 'telephone')->ignore($managerId),
            ],
            'whatsapp' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'RESIDENCE_LABEL_REQUIRED',
            'adresse.required' => 'RESIDENCE_ADDRESS_REQUIRED',
            'telephoneResidence.required' => 'RESIDENCE_PHONE_REQUIRED',
            'prenom.required' => 'RESIDENCE_MANAGER_FIRST_NAME_REQUIRED',
            'nom.required' => 'RESIDENCE_MANAGER_LAST_NAME_REQUIRED',
            'telephone.required' => 'RESIDENCE_MANAGER_PHONE_REQUIRED',
            'telephone.unique' => 'USER_PHONE_ALREADY_USED',
        ];
    }
}
