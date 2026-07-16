<?php

namespace App\Http\Requests\Api;

class ReservationUpdateRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:reservations,id'],
            'dateEntree' => ['required', 'date'],
            'dateSortie' => ['required', 'date', 'after_or_equal:dateEntree'],
            'presence' => ['required', 'boolean'],
            'chambre.id' => ['required', 'integer', 'exists:rooms,id'],
            'accueillant.id' => ['required', 'integer', 'exists:hosts,id'],
            'responsable.id' => ['required', 'integer', 'exists:room_managers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'dateEntree.required' => 'RESERVATION_ARRIVAL_DATE_REQUIRED',
            'dateSortie.required' => 'RESERVATION_DEPARTURE_DATE_REQUIRED',
            'chambre.id.required' => 'RESERVATION_GUEST_ROOM_REQUIRED',
        ];
    }
}
