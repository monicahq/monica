<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ActivateTemplate;
use App\Actions\DeactivateTemplate;
use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateActivationController extends Controller
{
    public function update(Request $request, string $template): JsonResponse
    {
        $template = $this->find($request, $template);

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $request->boolean('is_active')
            ? new ActivateTemplate(user: $request->user(), template: $template)->execute()
            : new DeactivateTemplate(user: $request->user(), template: $template)->execute();

        return new TemplateResource($template->refresh()->load(['structure.entityType', 'publishedVersion']))
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
