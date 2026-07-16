<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailableRoomStatsResource;
use App\Http\Resources\TotalStatsResource;
use App\Models\Pavilion;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function totals(int $residence): JsonResponse
    {
        $reservations = Reservation::whereHas('room.pavilion', fn (Builder $query) => $query->where('residence_id', $residence));
        $stats = [
            'pavillons' => Pavilion::where('residence_id', $residence)->count(),
            'chambres' => Room::whereHas('pavilion', fn (Builder $query) => $query->where('residence_id', $residence))->count(),
            'delegations' => (clone $reservations)->whereHas('guest.delegation')->join('guests', 'reservations.guest_id', '=', 'guests.id')->distinct('guests.delegation_id')->count('guests.delegation_id'),
            'reservations' => (clone $reservations)->count(),
        ];

        return response()->json((new TotalStatsResource($stats))->resolve());
    }

    public function availableRooms(int $residence): JsonResponse
    {
        $from = Carbon::now();
        $to = $from->copy()->addDays(30);
        $rooms = Room::query()
            ->with('pavilion')
            ->withCount(['reservations' => fn ($query) => $query
                ->where('date_entree', '<=', $to)
                ->where('date_sortie', '>=', $from)])
            ->whereHas('pavilion', fn (Builder $query) => $query->where('residence_id', $residence))
            ->get()
            ->filter(fn (Room $room) => $room->reservations_count < $room->nombre_place)
            ->groupBy('pavilion_id')
            ->map(fn ($rooms) => [
                'pavillon' => $rooms->first()->pavilion->libelle,
                'chambres' => $rooms->count(),
            ])->values();

        return response()->json(AvailableRoomStatsResource::collection($rooms)->resolve());
    }
}
