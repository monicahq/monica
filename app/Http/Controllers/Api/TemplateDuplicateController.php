<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\DuplicateTemplate;
use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateDuplicateController extends Controller
{
    public function create(Request $request, string $template): JsonResponse
    {
        $template = $this->find($request, $template);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $copy = new DuplicateTemplate(
            user: $request->user(),
            template: $template,
            name: $validated['name'] ?? null,
            slug: $validated['slug'] ?? null,
        )->execute();

        return new TemplateResource($copy->load(['structure.entityType', 'publishedVersion']))
            ->response()
            ->setStatusCode(201);
    }

    private function find(Request $request, string $uuid): Template
    {
        return $request->user()->account->templates()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
