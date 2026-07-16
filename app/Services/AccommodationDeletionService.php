<?php

namespace App\Services;

use App\Exceptions\AccommodationHasActivityException;
use App\Models\Pavilion;
use App\Models\Residence;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class AccommodationDeletionService
{
    public function deleteRoom(Room $room): void
    {
        DB::transaction(function () use ($room) {
            $room = Room::query()->lockForUpdate()->findOrFail($room->getKey());

            if ($room->reservations()->exists() || $room->managers()->exists()) {
                throw new AccommodationHasActivityException('CHAMBRE_HAS_ACTIVITY');
            }

            $room->delete();
        });
    }

    public function deletePavilion(Pavilion $pavilion): void
    {
        DB::transaction(function () use ($pavilion) {
            $pavilion = Pavilion::query()->lockForUpdate()->findOrFail($pavilion->getKey());
            $pavilion->rooms()->orderBy('id')->lockForUpdate()->get(['rooms.id']);

            if ($pavilion->rooms()->whereHas('reservations')->exists()
                || $pavilion->rooms()->whereHas('managers')->exists()) {
                throw new AccommodationHasActivityException('PAVILLON_HAS_ACTIVITY');
            }

            $pavilion->delete();
        });
    }

    public function deleteResidence(Residence $residence): void
    {
        DB::transaction(function () use ($residence) {
            $residence = Residence::query()->lockForUpdate()->findOrFail($residence->getKey());
            $residence->pavilions()->orderBy('id')->lockForUpdate()->get(['pavilions.id']);
            Room::query()
                ->whereHas('pavilion', fn ($query) => $query->where('residence_id', $residence->id))
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['rooms.id']);

            if ($residence->assignments()->exists()
                || $residence->hosts()->exists()
                || $residence->roomManagers()->exists()
                || $residence->pavilions()->whereHas('rooms.reservations')->exists()
                || $residence->pavilions()->whereHas('rooms.managers')->exists()) {
                throw new AccommodationHasActivityException('RESIDENCE_HAS_ACTIVITY');
            }

            $residence->delete();
        });
    }
}
