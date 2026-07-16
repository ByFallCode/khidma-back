<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AssignmentIndexRequest;
use App\Http\Requests\Api\AssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\Host;
use App\Support\FormatsSpringPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    use FormatsSpringPage;

    public function store(AssignmentRequest $request): JsonResponse
    {
        $assignment = DB::transaction(fn () => $this->persist(new Assignment, $request->validated()));

        return response()->json((new AssignmentResource($this->load($assignment)))->resolve());
    }

    public function update(AssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $assignment = DB::transaction(fn () => $this->persist($assignment, $request->validated()));

        return response()->json((new AssignmentResource($this->load($assignment)))->resolve());
    }

    public function show(Assignment $assignment): JsonResponse
    {
        return response()->json((new AssignmentResource($this->load($assignment)))->resolve());
    }

    public function index(AssignmentIndexRequest $request): JsonResponse
    {
        $data = $request->validated();
        $page = max(0, (int) $request->input('page', 0));
        $size = max(1, min((int) $request->input('size', 20), 100));
        $search = trim((string) $request->input('search', ''));

        $query = Assignment::with($this->relations())
            ->where('residence_id', $data['residenceId'])
            ->when($search !== '', fn ($q) => $q->whereHas('agent', fn ($agent) => $agent
                ->where('prenom', 'like', "%{$search}%")
                ->orWhere('nom', 'like', "%{$search}%")
                ->orWhere('telephone', 'like', "%{$search}%")))
            ->orderBy('id');

        $total = $query->count();
        $assignments = $query->skip($page * $size)->take($size)->get();
        $content = AssignmentResource::collection($assignments)->resolve();

        return response()->json($this->springPage($content, $page, $size, $total));
    }

    public function byAgent(int $agent): JsonResponse
    {
        $assignments = Assignment::with($this->relations())->where('agent_id', $agent)->orderBy('id')->get();

        return response()->json(AssignmentResource::collection($assignments)->resolve());
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        $assignment->delete();

        return response()->json(null, 200);
    }

    private function persist(Assignment $assignment, array $data): Assignment
    {
        $assignment->fill([
            'agent_id' => $data['agentId'],
            'residence_id' => $data['residenceId'],
            'start_date' => $data['startDate'] ?? null,
            'end_date' => $data['endDate'] ?? null,
        ])->save();

        if (in_array('ACCUEILLANT', $data['responsibilities'], true)) {
            Host::updateOrCreate(
                ['user_id' => $data['agentId']],
                ['residence_id' => $data['residenceId']],
            );
        }

        $assignment->responsibilityRows()->delete();
        $assignment->responsibilityRows()->createMany(collect($data['responsibilities'])->unique()->map(fn ($value) => ['responsibility' => $value])->all());
        $assignment->rotationSlots()->delete();
        $assignment->rotationSlots()->createMany(collect($data['rotationSlots'] ?? [])->map(fn ($slot) => [
            'day_of_week' => $slot['dayOfWeek'] ?? null,
            'from_time' => $slot['fromTime'],
            'to_time' => $slot['toTime'],
        ])->all());

        return $assignment;
    }

    private function load(Assignment $assignment): Assignment
    {
        return $assignment->load($this->relations());
    }

    private function relations(): array
    {
        return ['agent', 'residence', 'responsibilityRows', 'rotationSlots'];
    }
}
