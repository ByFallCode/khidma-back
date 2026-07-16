<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RotationSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dayOfWeek' => $this->day_of_week,
            'fromTime' => substr($this->from_time, 0, 5),
            'toTime' => substr($this->to_time, 0, 5),
        ];
    }
}
