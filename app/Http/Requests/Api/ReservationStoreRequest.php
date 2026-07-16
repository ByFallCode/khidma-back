<?php

namespace App\Http\Requests\Api;

class ReservationStoreRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'period' => ['required', 'array'],
            'period.entree' => ['required', 'date'],
            'period.sortie' => ['required', 'date', 'after_or_equal:period.entree'],
            'evenement.id' => ['required', 'integer', 'exists:events,id'],
            'delegation' => ['nullable', 'array'],
            'invites' => ['required', 'array', 'min:1'],
            'invites.*.prenom' => ['required', 'string', 'max:40'],
            'invites.*.nom' => ['required', 'string', 'max:20'],
            'invites.*.telephone' => ['required', 'string', 'exists:guests,telephone', 'distinct'],
            'invites.*.chambre.id' => ['required', 'integer', 'exists:rooms,id'],
            'invites.*.accueillant.id' => ['nullable', 'integer', 'exists:hosts,id'],
            'invites.*.responsable.id' => ['nullable', 'integer', 'exists:room_managers,id'],
            'invites.*.presence' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => 'RESERVATION_PERIOD_REQUIRED',
            'period.entree.required' => 'RESERVATION_ARRIVAL_DATE_REQUIRED',
            'period.sortie.required' => 'RESERVATION_DEPARTURE_DATE_REQUIRED',
            'evenement.id.required' => 'RESERVATION_EVENT_REQUIRED',
            'invites.required' => 'RESERVATION_GUESTS_REQUIRED',
            'invites.min' => 'RESERVATION_GUESTS_REQUIRED',
            'invites.*.prenom.required' => 'RESERVATION_GUEST_FIRST_NAME_REQUIRED',
            'invites.*.nom.required' => 'RESERVATION_GUEST_LAST_NAME_REQUIRED',
            'invites.*.telephone.required' => 'RESERVATION_GUEST_PHONE_REQUIRED',
            'invites.*.chambre.id.required' => 'RESERVATION_GUEST_ROOM_REQUIRED',
        ];
    }
}
