<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePavilionRequest;
use App\Http\Requests\Api\UpdatePavilionRequest;
use App\Http\Resources\PavilionResource;
use App\Models\Pavilion;
use App\Models\Room;
use App\Services\AccommodationDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PavilionController extends Controller
{
    public function __construct(private readonly AccommodationDeletionService $deletionService) {}

    public function index(): JsonResponse
    {
        $pavilions = Pavilion::with(['residence', 'rooms.pavilion.residence'])->orderBy('id')->get();

        return response()->json(PavilionResource::collection($pavilions)->resolve());
    }

    public function store(StorePavilionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $pavilion = DB::transaction(function () use ($data) {
            $pavilion = Pavilion::create([
                'libelle' => $data['libelle'],
                'niveau' => $data['niveau'] ?? 0,
                'residence_id' => $data['residence']['id'],
            ]);

            foreach ($data['chambres'] ?? [] as $room) {
                $floor = $room['niveau'] ?? 0;
                Room::create([
                    'numero' => $room['numero'],
                    'nombre_place' => $room['nombrePlace'],
                    'niveau' => $floor,
                    'reference' => $this->reference($pavilion->libelle, $floor, $room['numero']),
                    'pavilion_id' => $pavilion->id,
                ]);
            }

            return $pavilion;
        });

        return response()->json((new PavilionResource($this->load($pavilion)))->resolve());
    }

    public function update(UpdatePavilionRequest $request, Pavilion $pavilion): JsonResponse
    {
        $data = $request->validated();

        $pavilion->update($data);

        return response()->json((new PavilionResource($this->load($pavilion)))->resolve());
    }

    public function show(Pavilion $pavilion): JsonResponse
    {
        return response()->json((new PavilionResource($this->load($pavilion)))->resolve());
    }

    public function byResidence(int $residence): JsonResponse
    {
        $pavilions = Pavilion::with(['residence', 'rooms.pavilion.residence'])
            ->where('residence_id', $residence)->orderBy('id')->get();

        return response()->json(PavilionResource::collection($pavilions)->resolve());
    }

    public function destroy(Pavilion $pavilion): JsonResponse
    {
        $this->deletionService->deletePavilion($pavilion);

        return response()->json()->setData(null);
    }

    private function load(Pavilion $pavilion): Pavilion
    {
        return $pavilion->load(['residence', 'rooms.pavilion.residence']);
    }

    private function reference(string $label, int $floor, string $number): string
    {
        $initials = collect(preg_split('/\s+/u', trim($label)))
            ->filter()->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))->join('');

        return $initials.'-'.$floor.$number;
    }
}
