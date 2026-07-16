<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PaginationRequest;
use App\Http\Requests\Api\ReservationStoreRequest;
use App\Http\Requests\Api\ReservationUpdateRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Support\FormatsSpringPage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    use FormatsSpringPage;

    public function store(ReservationStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $reservations = DB::transaction(function () use ($data) {
            $from = Carbon::parse($data['period']['entree']);
            $to = Carbon::parse($data['period']['sortie']);
            $selectedRooms = collect($data['invites'])->countBy(fn (array $guest) => $guest['chambre']['id']);

            foreach ($selectedRooms as $roomId => $requestedPlaces) {
                $this->ensureCapacity((int) $roomId, $from, $to, (int) $requestedPlaces);
            }

            return collect($data['invites'])->map(function (array $guest) use ($data, $from, $to) {
                $guestModel = Guest::where('telephone', $guest['telephone'])->firstOrFail();

                return Reservation::create([
                    'date_entree' => $from,
                    'date_sortie' => $to,
                    'date_sortie_provisoire' => $to,
                    'event_id' => $data['evenement']['id'],
                    'room_id' => $guest['chambre']['id'],
                    'guest_id' => $guestModel->id,
                    'host_id' => $guest['accueillant']['id'] ?? null,
                    'room_manager_id' => $guest['responsable']['id'] ?? null,
                    'presence' => $guest['presence'] ?? null,
                ]);
            })->map(fn (Reservation $reservation) => $this->load($reservation));
        });

        return response()->json(ReservationResource::collection($reservations)->resolve());
    }

    public function update(ReservationUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $reservation = DB::transaction(function () use ($data) {
            $reservation = Reservation::lockForUpdate()->findOrFail($data['id']);
            $from = Carbon::parse($data['dateEntree']);
            $to = Carbon::parse($data['dateSortie']);
            $this->ensureCapacity($data['chambre']['id'], $from, $to, 1, $reservation->id);
            $reservation->update([
                'date_entree' => $from,
                'date_sortie' => $to,
                'date_sortie_provisoire' => $to,
                'presence' => $data['presence'],
                'room_id' => $data['chambre']['id'],
                'host_id' => $data['accueillant']['id'],
                'room_manager_id' => $data['responsable']['id'],
            ]);

            return $reservation;
        });

        return response()->json((new ReservationResource($this->load($reservation)))->resolve());
    }

    public function index(PaginationRequest $request): JsonResponse
    {
        $page = max(0, (int) $request->input('page', 0));
        $size = max(1, min((int) $request->input('size', 20), 100));
        $query = $this->query()->orderBy('date_entree');
        $this->applyFilters($query, $request->integer('year', -1), $request->integer('event', -1), $request->integer('residence', -1), $request->integer('presence', -1));
        $total = $query->count();
        $content = ReservationResource::collection($query->skip($page * $size)->take($size)->get())->resolve();

        return response()->json($this->springPage($content, $page, $size, $total, true));
    }

    public function show(Reservation $reservation): JsonResponse
    {
        return response()->json((new ReservationResource($this->load($reservation)))->resolve());
    }

    public function byPavilion(string $pavilion, string $from, string $to): JsonResponse
    {
        $fromDate = Carbon::parse($from);
        $toDate = Carbon::parse($to);
        $reservations = $this->query()
            ->whereHas('room', fn (Builder $query) => $query->where('pavilion_id', $pavilion))
            ->where('date_entree', '<=', $toDate)
            ->where('date_sortie', '>=', $fromDate)
            ->orderBy('date_entree')->get();

        return response()->json(ReservationResource::collection($reservations)->resolve());
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        $reservation->delete();

        return response()->json(null, 200);
    }

    private function ensureCapacity(int $roomId, Carbon $from, Carbon $to, int $requested, ?int $except = null): void
    {
        $room = Room::lockForUpdate()->findOrFail($roomId);
        $booked = Reservation::where('room_id', $roomId)
            ->where('date_entree', '<=', $to)
            ->where('date_sortie', '>=', $from)
            ->when($except, fn (Builder $query) => $query->whereKeyNot($except))
            ->count();

        if ($booked + $requested > $room->nombre_place) {
            throw ValidationException::withMessages(['chambre' => ['RESERVATION_ROOM_CAPACITY_EXCEEDED']]);
        }
    }

    private function applyFilters(Builder $query, int $year, int $event, int $residence, int $presence): void
    {
        $query->when($event !== -1, fn (Builder $q) => $q->where('event_id', $event))
            ->when($residence !== -1, fn (Builder $q) => $q->whereHas('room.pavilion', fn (Builder $p) => $p->where('residence_id', $residence)))
            ->when($year !== -1, fn (Builder $q) => $q->where(fn (Builder $dates) => $dates->whereYear('date_entree', $year)->orWhereYear('date_sortie', $year)))
            ->when($presence !== -1, fn (Builder $q) => $q->where('presence', $presence === 1));
    }

    private function query(): Builder
    {
        return Reservation::with([
            'event', 'room.pavilion.residence', 'guest.delegation',
            'host.user', 'host.residence', 'roomManager.residence',
        ]);
    }

    private function load(Reservation $reservation): Reservation
    {
        return $this->query()->findOrFail($reservation->id);
    }
}
