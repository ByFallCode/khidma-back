<?php

namespace App\Services;

use App\Exceptions\AccommodationHasActivityException;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;

class GuestDeletionService
{
    public function delete(Guest $guest): void
    {
        DB::transaction(function () use ($guest) {
            $guest = Guest::query()->lockForUpdate()->findOrFail($guest->getKey());

            if ($guest->reservations()->exists()) {
                throw new AccommodationHasActivityException('INVITE_HAS_ACTIVITY');
            }

            $guest->delete();
        });
    }
}
