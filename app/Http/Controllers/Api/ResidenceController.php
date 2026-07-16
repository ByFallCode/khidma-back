<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreResidenceRequest;
use App\Http\Requests\Api\UpdateResidenceRequest;
use App\Http\Resources\ResidenceResource;
use App\Models\Residence;
use App\Models\Resource;
use App\Models\User;
use App\Services\AccommodationDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ResidenceController extends Controller
{
    public function __construct(private readonly AccommodationDeletionService $deletionService) {}

    public function index(): JsonResponse
    {
        $residences = Residence::with(['image', 'responsable', 'pavilions.residence', 'pavilions.rooms.pavilion.residence'])
            ->orderBy('id')->get();

        return response()->json(ResidenceResource::collection($residences)->resolve());
    }

    public function store(StoreResidenceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $residence = DB::transaction(function () use ($data, $request) {
            $manager = User::create([
                'username' => $data['telephone'],
                'password' => 'test',
                'account_type' => 'KHIDMA_AGENT',
                'statut' => true,
                'prenom' => $data['prenom'],
                'nom' => $data['nom'],
                'telephone' => $data['telephone'],
            ]);

            $resource = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $resource = Resource::create([
                    'type' => $image->getMimeType() ?: 'application/octet-stream',
                    'nom' => $image->getClientOriginalName(),
                    'path' => $image->store('residences', 'local'),
                ]);
            }

            return Residence::create([
                'libelle' => $data['libelle'],
                'adresse' => $data['adresse'],
                'telephone_residence' => $data['telephoneResidence'],
                'responsable_id' => $manager->id,
                'image_id' => $resource?->id,
            ]);
        });

        return response()->json((new ResidenceResource($this->load($residence)))->resolve());
    }

    public function update(UpdateResidenceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $residence = Residence::findOrFail($data['id']);
        $residence->update([
            'libelle' => $data['libelle'],
            'adresse' => $data['adresse'],
            'telephone_residence' => $data['telephoneResidence'],
            'responsable_id' => $data['responsable']['id'],
        ]);

        return response()->json((new ResidenceResource($this->load($residence)))->resolve());
    }

    public function show(Residence $residence): JsonResponse
    {
        return response()->json((new ResidenceResource($this->load($residence)))->resolve());
    }

    public function byManager(string $username): JsonResponse
    {
        $residence = Residence::whereHas('responsable', fn ($query) => $query->where('username', 'like', "%{$username}%"))->firstOrFail();

        return response()->json((new ResidenceResource($this->load($residence)))->resolve());
    }

    public function destroy(Residence $residence): JsonResponse
    {
        $this->deletionService->deleteResidence($residence);

        return response()->json()->setData(null);
    }

    private function load(Residence $residence): Residence
    {
        return $residence->load(['image', 'responsable', 'pavilions.residence', 'pavilions.rooms.pavilion.residence']);
    }
}
