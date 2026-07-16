<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PaginationRequest;
use App\Http\Requests\Api\StoreHostRequest;
use App\Http\Requests\Api\UpdateHostRequest;
use App\Http\Resources\HostResource;
use App\Models\Host;
use App\Models\User;
use App\Support\FormatsSpringPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HostController extends Controller
{
    use FormatsSpringPage;

    public function store(StoreHostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $host = DB::transaction(function () use ($data) {
            $user = User::create([
                'username' => $data['utilisateur']['telephone'],
                'password' => 'test',
                'account_type' => 'KHIDMA_AGENT',
                'statut' => true,
                'prenom' => $data['utilisateur']['prenom'],
                'nom' => $data['utilisateur']['nom'],
                'telephone' => $data['utilisateur']['telephone'],
            ]);

            return Host::create(['user_id' => $user->id, 'residence_id' => $data['residence']['id']]);
        });

        return response()->json((new HostResource($this->load($host)))->resolve());
    }

    public function update(UpdateHostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $id = $data['id'];
        $host = Host::with('user')->findOrFail($id);

        DB::transaction(function () use ($host, $data) {
            $host->user->update([
                'username' => $data['utilisateur']['telephone'],
                'prenom' => $data['utilisateur']['prenom'],
                'nom' => $data['utilisateur']['nom'],
                'telephone' => $data['utilisateur']['telephone'],
            ]);
            $host->update(['residence_id' => $data['residence']['id']]);
        });

        return response()->json((new HostResource($this->load($host)))->resolve());
    }

    public function index(PaginationRequest $request): JsonResponse
    {
        $page = max(0, (int) $request->input('page', 0));
        $size = max(1, min((int) $request->input('size', 20), 100));
        $residence = (int) $request->input('residence', -1);
        $search = trim((string) $request->input('search', ''));

        $query = Host::with(['user', 'residence'])
            ->when($residence !== -1, fn ($q) => $q->where('residence_id', $residence))
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($user) => $user
                ->where('nom', 'like', "%{$search}%")
                ->orWhere('prenom', 'like', "%{$search}%")
                ->orWhere('telephone', 'like', "%{$search}%")))
            ->orderBy('id');

        $total = $query->count();
        $hosts = $query->skip($page * $size)->take($size)->get();
        $content = HostResource::collection($hosts)->resolve();

        return response()->json($this->springPage($content, $page, $size, $total));
    }

    public function show(Host $host): JsonResponse
    {
        return response()->json((new HostResource($this->load($host)))->resolve());
    }

    public function byUsername(string $username): JsonResponse
    {
        $host = Host::with(['user', 'residence'])->whereHas('user', fn ($query) => $query->where('username', 'like', "%{$username}%"))->firstOrFail();

        return response()->json((new HostResource($host))->resolve());
    }

    public function destroy(Host $host): JsonResponse
    {
        DB::transaction(function () use ($host) {
            $user = $host->user;
            $host->delete();
            $user->delete();
        });

        return response()->json(null, 200);
    }

    private function load(Host $host): Host
    {
        return $host->load(['user', 'residence']);
    }
}
