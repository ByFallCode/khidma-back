<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DelegationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $leader = $this->guests->firstWhere('est_responsable', true);
        $guests = $this->guests->where('est_responsable', false)->values();

        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'nombre' => $this->nombre,
            'chef' => $leader ? new GuestResource($leader) : null,
            'invites' => GuestResource::collection($guests),
        ];
    }
}
