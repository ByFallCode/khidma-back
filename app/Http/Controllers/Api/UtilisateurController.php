<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangeOwnPasswordRequest;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\PaginationRequest;
use App\Http\Requests\Api\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UtilisateurController extends Controller
{
    public function index(PaginationRequest $request): JsonResponse
    {
        $size = max(1, min((int) $request->input('size', 20), 100));
        $page = max(0, (int) $request->input('page', 0));
        $search = trim((string) $request->input('search', ''));

        $accountType = (string) $request->input('accountType', '');
        $query = User::query()
            ->when($accountType === 'KHIDMA_AGENT', fn ($q) => $q->with('assignments.residence'))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('prenom', 'like', "%{$search}%")
                    ->orWhere('nom', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            }))
            ->when($accountType !== '', fn ($q) => $q->where('account_type', $accountType === 'responsable' ? 'KHIDMA_AGENT' : $accountType))
            ->orderBy('id');

        $total = $query->count();
        $users = $query->skip($page * $size)->take($size)->get();
        $content = UserResource::collection($users)->resolve();

        return response()->json($this->springPage($content, $page, $size, $total));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['username'] = $data['telephone'];
        $data['password'] = $data['password'] ?? 'test';
        $data['account_type'] = $data['accountType'] ?? 'KHIDMA_AGENT';
        $data['statut'] = true;
        unset($data['accountType']);

        return response()->json((new UserResource(User::create($data)))->resolve());
    }

    public function show(User $user): JsonResponse
    {
        return response()->json((new UserResource($user))->resolve());
    }

    public function account(Request $request): JsonResponse
    {
        return response()->json((new UserResource($request->user()))->resolve());
    }

    public function toggleStatus(User $user): JsonResponse
    {
        $user->update(['statut' => ! $user->statut]);

        return response()->json((new UserResource($user->fresh()))->resolve());
    }

    public function changePassword(ChangePasswordRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $user->update(['password' => $data['password']]);

        return response()->json(null, 200);
    }

    public function changeOwnPassword(ChangeOwnPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (! Hash::check($data['currentPassword'], $user->password)) {
            return response()->json(['code' => 'AUTH_BAD_CREDENTIALS'], 400);
        }

        $user->update(['password' => $data['newPassword']]);

        return response()->json(null, 200);
    }

    private function springPage(array $content, int $page, int $size, int $total): array
    {
        $pages = $total === 0 ? 0 : (int) ceil($total / $size);
        $sort = ['empty' => true, 'sorted' => false, 'unsorted' => true];

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
