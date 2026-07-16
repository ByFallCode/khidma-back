<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableRoomStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'pavillon' => $this['pavillon'],
            'chambres' => (int) $this['chambres'],
        ];
    }
}
