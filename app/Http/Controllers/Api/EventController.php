<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function store(EventRequest $request): JsonResponse
    {
        $data = $request->validated();
        $event = isset($data['id']) ? Event::findOrFail($data['id']) : new Event;
        $event->fill([
            'libelle' => $data['libelle'],
            'date_debut' => $data['dateDebut'] ?? null,
            'date_fin' => $data['dateFin'] ?? null,
        ])->save();

        return response()->json((new EventResource($event))->resolve());
    }

    public function index(): JsonResponse
    {
        return response()->json(EventResource::collection(Event::orderBy('id')->get())->resolve());
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json((new EventResource($event))->resolve());
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json(null, 200);
    }
}
