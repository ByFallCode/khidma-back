<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'utilisateur' => new UserResource($this->whenLoaded('user')),
            'residence' => new ResidenceSummaryResource($this->whenLoaded('residence')),
        ];
    }
}
