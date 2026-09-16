<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Actions\BuildOpenApiDocument;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class OpenApiDocumentController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::rememberForever('openapi_document', fn () => new BuildOpenApiDocument()->execute());

        return response()->json(
            $data,
            200,
            [],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );
    }
}
