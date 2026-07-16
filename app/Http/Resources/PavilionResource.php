<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PavilionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'niveau' => $this->niveau,
            'residence' => new ResidenceSummaryResource($this->whenLoaded('residence')),
            'chambres' => RoomResource::collection($this->whenLoaded('rooms')),
        ];
    }
}
