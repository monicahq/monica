<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Actions\BuildOpenApiDocument;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenApiDocumentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            new BuildOpenApiDocument()->execute(),
            200,
            [],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );
    }
}
