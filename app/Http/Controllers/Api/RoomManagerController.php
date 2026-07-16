<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RoomManagerIndexRequest;
use App\Http\Requests\Api\RoomManagerRequest;
use App\Http\Resources\RoomManagerResource;
use App\Models\RoomManager;
use App\Support\FormatsSpringPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RoomManagerController extends Controller
{
    use FormatsSpringPage;

    public function store(RoomManagerRequest $request): JsonResponse
    {
        $data = $request->validated();

        $manager = DB::transaction(function () use ($data) {
            $manager = isset($data['id']) ? RoomManager::findOrFail($data['id']) : new RoomManager;
            $manager->fill([
                'prenom' => $data['prenom'],
                'nom' => $data['nom'],
                'telephone' => $data['telephone'],
                'residence_id' => $data['residence']['id'],
            ])->save();
            $manager->rooms()->sync(collect($data['chambres'] ?? [])->pluck('id'));

            return $manager;
        });

        return response()->json((new RoomManagerResource($this->load($manager)))->resolve());
    }

    public function index(RoomManagerIndexRequest $request): JsonResponse
    {
        $page = max(0, (int) $request->input('page', 0));
        $size = max(1, min((int) $request->input('size', 20), 200));
        $residence = (int) $request->input('residence', -1);
        $search = trim((string) $request->input('search', ''));

        $query = RoomManager::with($this->relations())
            ->when($residence !== -1, fn ($q) => $q->where('residence_id', $residence))
            ->when($search !== '', fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            }))->orderBy('id');

        $total = $query->count();
        $managers = $query->skip($page * $size)->take($size)->get();
        $content = RoomManagerResource::collection($managers)->resolve();

        return response()->json($this->springPage($content, $page, $size, $total));
    }

    public function show(RoomManager $roomManager): JsonResponse
    {
        return response()->json((new RoomManagerResource($this->load($roomManager)))->resolve());
    }

    public function destroy(RoomManager $roomManager): JsonResponse
    {
        $roomManager->delete();

        return response()->json(null, 200);
    }

    private function load(RoomManager $manager): RoomManager
    {
        return $manager->load($this->relations());
    }

    private function relations(): array
    {
        return ['residence.image', 'residence.responsable', 'residence.pavilions.residence', 'residence.pavilions.rooms.pavilion.residence'];
    }
}
