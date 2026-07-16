<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombrePlace' => $this->nombre_place,
            'numero' => $this->numero,
            'niveau' => $this->niveau,
            'reference' => $this->reference,
            'placeReservee' => (int) ($this->placeReservee ?? 0),
            'pavillon' => new PavilionSummaryResource($this->whenLoaded('pavilion')),
        ];
    }
}
