<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'dateDebut' => $this->date_debut?->format('Y-m-d'),
            'dateFin' => $this->date_fin?->format('Y-m-d'),
        ];
    }
}
