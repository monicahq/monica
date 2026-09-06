<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateTemplate;
use App\Actions\DestroyTemplate;
use App\Actions\UpdateTemplate;
use App\Enums\TemplateAppliesToEnum;
use App\Exceptions\CannotDeleteATemplateInUse;
use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use App\Models\EntityType;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $templates = $request->user()->account->templates()
            ->with(['entityType', 'publishedVersion'])
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => TemplateResource::collection($templates->items())->resolve(),
            'meta' => [
                'page' => $templates->currentPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    public function show(Request $request, string $template): JsonResponse
    {
        return new TemplateResource($this->find($request, $template))->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'applies_to' => ['required', Rule::enum(TemplateAppliesToEnum::class)],
            'description' => ['nullable', 'string', 'max:65535'],
            'entity_type_id' => [
                Rule::requiredIf(fn (): bool => $request->string('applies_to')->value() === TemplateAppliesToEnum::Entity->value),
                'nullable',
                'string',
                Rule::exists('entity_types', 'uuid')->where('account_id', $request->user()->account_id),
            ],
        ]);

        $template = new CreateTemplate(
            user: $request->user(),
            name: $validated['name'],
            appliesTo: TemplateAppliesToEnum::from($validated['applies_to']),
            entityType: $this->entityType($request, $validated['entity_type_id'] ?? null),
            description: $validated['description'] ?? null,
        )->execute();

        return new TemplateResource($template->load(['entityType', 'publishedVersion']))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $template): JsonResponse
    {
        $template = $this->find($request, $template);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['present', 'nullable', 'string', 'max:65535'],
        ]);

        new UpdateTemplate(
            user: $request->user(),
            template: $template,
            name: $validated['name'],
            description: $validated['description'],
        )->execute();

        return new TemplateResource($template->refresh()->load(['entityType', 'publishedVersion']))
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Request $request, string $template): Response
    {
        try {
            new DestroyTemplate(
                user: $request->user(),
                template: $this->find($request, $template),
            )->execute();
        } catch (CannotDeleteATemplateInUse $e) {
            throw ValidationException::withMessages(['template' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function entityType(Request $request, ?string $uuid): ?EntityType
    {
        if ($uuid === null) {
            return null;
        }

        return $request->user()->account->entityTypes()->where('uuid', $uuid)->firstOrFail();
    }

    private function find(Request $request, string $uuid): Template
    {
        return $request->user()->account->templates()
            ->with(['entityType', 'publishedVersion'])
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
