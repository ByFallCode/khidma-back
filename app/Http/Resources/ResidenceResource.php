<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'adresse' => $this->adresse,
            'telephoneResidence' => $this->telephone_residence,
            'archive' => $this->archive,
            'image' => $this->image ? new StoredResource($this->image) : null,
            'responsable' => $this->responsable ? new UserResource($this->responsable) : null,
            'pavillons' => PavilionResource::collection($this->whenLoaded('pavilions')),
        ];
    }
}
