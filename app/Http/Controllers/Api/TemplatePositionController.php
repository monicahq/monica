<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateTemplatePosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplatePositionController extends Controller
{
    public function update(Request $request, string $template): JsonResponse
    {
        $template = $this->find($request, $template);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateTemplatePosition(
            user: $request->user(),
            template: $template,
            position: (int) $validated['position'],
        )->execute();

        return new TemplateResource($template->refresh()->load(['entityType', 'publishedVersion']))
            ->response()
            ->setStatusCode(200);
    }

    private function find(Request $request, string $uuid): Template
    {
        return $request->user()->account->templates()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
