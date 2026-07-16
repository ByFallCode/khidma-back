<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent' => new UserResource($this->whenLoaded('agent')),
            'residence' => new ResidenceSummaryResource($this->whenLoaded('residence')),
            'responsibilities' => $this->whenLoaded('responsibilityRows', fn () => $this->responsibilityRows->pluck('responsibility')->values()),
            'rotationSlots' => RotationSlotResource::collection($this->whenLoaded('rotationSlots')),
            'startDate' => $this->start_date?->format('Y-m-d'),
            'endDate' => $this->end_date?->format('Y-m-d'),
        ];
    }
}
