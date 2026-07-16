<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(RoleResource::collection(collect([
            ['id' => 1, 'libelle' => 'ADMIN'],
            ['id' => 2, 'libelle' => 'KHIDMA_AGENT'],
        ]))->resolve());
    }
}
