<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TotalStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'pavillons' => (int) $this['pavillons'],
            'chambres' => (int) $this['chambres'],
            'delegations' => (int) $this['delegations'],
            'reservations' => (int) $this['reservations'],
        ];
    }
}
