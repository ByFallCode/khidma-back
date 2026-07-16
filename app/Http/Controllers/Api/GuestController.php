<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GuestRequest;
use App\Http\Resources\GuestResource;
use App\Models\Guest;
use App\Services\GuestDeletionService;
use Illuminate\Http\JsonResponse;

class GuestController extends Controller
{
    public function __construct(private readonly GuestDeletionService $deletionService) {}

    public function store(GuestRequest $request): JsonResponse
    {
        $data = $request->validated();
        $guest = Guest::create([
            'prenom' => $data['prenom'],
            'nom' => $data['nom'],
            'telephone' => $data['telephone'],
            'adresse' => $data['adresse'] ?? null,
            'email' => $data['email'] ?? null,
            'est_responsable' => $data['estResponsable'] ?? false,
            'delegation_id' => $data['delegation']['id'],
        ])->load('delegation');

        return response()->json((new GuestResource($guest))->resolve());
    }

    public function destroy(Guest $guest): JsonResponse
    {
        $this->deletionService->delete($guest);

        return response()->json()->setData(null);
    }
}
