<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DelegationRequest;
use App\Http\Requests\Api\PaginationRequest;
use App\Http\Resources\DelegationResource;
use App\Models\Delegation;
use App\Support\FormatsSpringPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DelegationController extends Controller
{
    use FormatsSpringPage;

    public function store(DelegationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $delegation = DB::transaction(function () use ($data) {
            $delegation = Delegation::create(['nom' => $data['nom'], 'nombre' => $data['nombre']]);
            $delegation->guests()->create($this->guestAttributes($data['chef'], true));
            $delegation->guests()->createMany(collect($data['invites'])->map(fn ($guest) => $this->guestAttributes($guest, false))->all());

            return $delegation;
        });

        return response()->json((new DelegationResource($this->load($delegation)))->resolve());
    }

    public function update(DelegationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $delegation = Delegation::findOrFail($data['id']);
        $delegation->update(['nom' => $data['nom'], 'nombre' => $data['nombre']]);

        return response()->json((new DelegationResource($this->load($delegation)))->resolve());
    }

    public function index(PaginationRequest $request): JsonResponse
    {
        $page = max(0, (int) $request->input('page', 0));
        $size = max(1, min((int) $request->input('size', 20), 100));
        $query = Delegation::with('guests.delegation')->orderBy('id');
        $total = $query->count();
        $delegations = $query->skip($page * $size)->take($size)->get();
        $content = DelegationResource::collection($delegations)->resolve();

        return response()->json($this->springPage($content, $page, $size, $total));
    }

    public function show(Delegation $delegation): JsonResponse
    {
        return response()->json((new DelegationResource($this->load($delegation)))->resolve());
    }

    public function destroy(Delegation $delegation): JsonResponse
    {
        $delegation->delete();

        return response()->json(null, 200);
    }

    private function load(Delegation $delegation): Delegation
    {
        return $delegation->load('guests.delegation');
    }

    private function guestAttributes(array $guest, bool $leader): array
    {
        return [
            'prenom' => $guest['prenom'],
            'nom' => $guest['nom'],
            'telephone' => $guest['telephone'],
            'adresse' => $guest['adresse'] ?? null,
            'email' => $guest['email'] ?? null,
            'est_responsable' => $leader,
        ];
    }
}
