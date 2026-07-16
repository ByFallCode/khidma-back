<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ResourceController extends Controller
{
    public function show(Resource $resource): Response
    {
        abort_unless(Storage::disk('local')->exists($resource->path), 404);

        return response(Storage::disk('local')->get($resource->path), 200, [
            'Content-Type' => $resource->type,
            'Content-Disposition' => 'inline; filename="'.addslashes($resource->nom).'"',
        ]);
    }
}
