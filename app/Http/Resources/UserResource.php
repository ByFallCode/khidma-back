<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'accountType' => $this->account_type,
            'statut' => $this->statut,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'whatsapp' => $this->whatsapp,
        ];

        if ($this->resource->relationLoaded('assignments')) {
            $assignment = $this->assignments->first();
            $data['hasAssignment'] = $assignment !== null;
            $data['assignedResidenceName'] = $assignment?->residence?->libelle;
        }

        return $data;
    }
}
