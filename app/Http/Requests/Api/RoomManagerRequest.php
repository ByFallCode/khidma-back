<?php

namespace App\Http\Requests\Api;

use App\Models\Room;
use Illuminate\Validation\Validator;

class RoomManagerRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer', 'exists:room_managers,id'],
            'prenom' => ['required', 'string'],
            'nom' => ['required', 'string'],
            'telephone' => ['required', 'string'],
            'residence.id' => ['required', 'integer', 'exists:residences,id'],
            'chambres' => ['nullable', 'array'],
            'chambres.*.id' => ['required', 'integer', 'exists:rooms,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'prenom.required' => 'RESPONSABLE_FIRST_NAME_REQUIRED',
            'nom.required' => 'RESPONSABLE_LAST_NAME_REQUIRED',
            'telephone.required' => 'RESPONSABLE_PHONE_REQUIRED',
            'residence.id.required' => 'RESPONSABLE_RESIDENCE_REQUIRED',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $residenceId = (int) $this->input('residence.id');
            $roomIds = collect($this->input('chambres', []))->pluck('id')->filter()->all();

            if ($residenceId === 0 || $roomIds === []) {
                return;
            }

            $hasRoomFromAnotherResidence = Room::query()
                ->whereIn('id', $roomIds)
                ->whereDoesntHave('pavilion', fn ($query) => $query->where('residence_id', $residenceId))
                ->exists();

            if ($hasRoomFromAnotherResidence) {
                $validator->errors()->add('chambres', 'RESPONSABLE_ROOM_RESIDENCE_MISMATCH');
            }
        });
    }
}
