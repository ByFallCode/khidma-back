<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'dateEntree' => $this->date_entree?->toISOString(),
            'dateSortie' => $this->date_sortie?->toISOString(),
            'dateSortieProvisoire' => $this->date_sortie_provisoire?->toISOString(),
            'statut' => $this->statut,
            'presence' => $this->presence,
            'evenement' => new EventResource($this->whenLoaded('event')),
            'chambre' => new RoomResource($this->whenLoaded('room')),
            'invite' => new GuestResource($this->whenLoaded('guest')),
            'accueillant' => $this->host ? new HostResource($this->host) : null,
            'responsable' => $this->roomManager ? new RoomManagerResource($this->roomManager) : null,
        ];
    }
}
