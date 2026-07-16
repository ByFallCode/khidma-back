<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'email' => $this->email,
            'estResponsable' => $this->est_responsable,
            'delegation' => new DelegationSummaryResource($this->whenLoaded('delegation')),
        ];
    }
}
