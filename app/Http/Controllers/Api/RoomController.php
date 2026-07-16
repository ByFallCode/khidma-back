<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PaginationRequest;
use App\Http\Requests\Api\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Pavilion;
use App\Models\Room;
use App\Services\AccommodationDeletionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function __construct(private readonly AccommodationDeletionService $deletionService) {}

    public function store(RoomRequest $request): JsonResponse
    {
        $data = $request->validated();
        $pavilion = Pavilion::findOrFail($data['pavillon']['id']);
        $room = Room::create([
            'nombre_place' => $data['nombrePlace'],
            'numero' => $data['numero'],
            'niveau' => $data['niveau'] ?? 0,
            'reference' => $this->reference($pavilion, $data['niveau'] ?? 0, $data['numero']),
            'pavilion_id' => $pavilion->id,
        ]);

        return response()->json((new RoomResource($this->load($room)))->resolve());
    }

    public function update(RoomRequest $request): JsonResponse
    {
        $data = $request->validated();
        $room = Room::findOrFail($data['id']);
        $pavilion = Pavilion::findOrFail($data['pavillon']['id']);
        $room->update([
            'nombre_place' => $data['nombrePlace'],
            'numero' => $data['numero'],
            'niveau' => $data['niveau'] ?? 0,
            'reference' => $this->reference($pavilion, $data['niveau'] ?? 0, $data['numero']),
            'pavilion_id' => $pavilion->id,
        ]);

        return response()->json((new RoomResource($this->load($room)))->resolve());
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json((new RoomResource($this->load($room)))->resolve());
    }

    public function byPavilion(PaginationRequest $request, Pavilion $pavilion): JsonResponse
    {
        $page = max(0, (int) $request->input('page', 0));
        $size = max(1, min((int) $request->input('size', 10), 100));
        $query = Room::with('pavilion.residence')->where('pavilion_id', $pavilion->id)->orderBy('numero');
        $total = $query->count();
        $rooms = $query->skip($page * $size)->take($size)->get();
        $content = RoomResource::collection($rooms)->resolve();

        return response()->json($this->springPage($content, $page, $size, $total));
    }

    public function availableByResidence(int $residence, string $from, string $to): JsonResponse
    {
        $fromDate = Carbon::parse($this->normalizeClientDate($from));
        $toDate = Carbon::parse($this->normalizeClientDate($to));
        $rooms = Room::with('pavilion.residence')
            ->withCount(['reservations' => fn ($query) => $query
                ->where('date_entree', '<=', $toDate)
                ->where('date_sortie', '>=', $fromDate)])
            ->whereHas('pavilion', fn ($query) => $query->where('residence_id', $residence))
            ->orderBy('numero')->get()
            ->each(fn (Room $room) => $room->placeReservee = $room->reservations_count)
            ->filter(fn (Room $room) => $room->reservations_count < $room->nombre_place)
            ->values();

        return response()->json(RoomResource::collection($rooms)->resolve());
    }

    public function destroy(Room $room): JsonResponse
    {
        $this->deletionService->deleteRoom($room);

        return response()->json()->setData(null);
    }

    private function normalizeClientDate(string $date): string
    {
        return preg_replace('/\s*\([^()]*\)\s*$/u', '', trim($date)) ?? $date;
    }

    private function load(Room $room): Room
    {
        return $room->load('pavilion.residence');
    }

    private function reference(Pavilion $pavilion, int $floor, string $number): string
    {
        $initials = collect(preg_split('/\s+/u', trim($pavilion->libelle)))
            ->filter()->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))->join('');

        return $initials.'-'.$floor.$number;
    }

    private function springPage(array $content, int $page, int $size, int $total): array
    {
        $pages = $total === 0 ? 0 : (int) ceil($total / $size);
        $sort = ['empty' => false, 'sorted' => true, 'unsorted' => false];

        return [
            'content' => $content,
            'pageable' => ['pageNumber' => $page, 'pageSize' => $size, 'sort' => $sort, 'offset' => $page * $size, 'paged' => true, 'unpaged' => false],
            'last' => $pages === 0 || $page >= $pages - 1,
            'totalPages' => $pages,
            'totalElements' => $total,
            'size' => $size,
            'number' => $page,
            'sort' => $sort,
            'first' => $page === 0,
            'numberOfElements' => count($content),
            'empty' => count($content) === 0,
        ];
    }
}
