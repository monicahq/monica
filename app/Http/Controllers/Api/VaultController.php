<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateVault;
use App\Actions\DestroyVault;
use App\Actions\SetDefaultVault;
use App\Actions\SetVaultDefaultTemplate;
use App\Actions\UpdateVault;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\VaultColourEnum;
use App\Exceptions\TooManyVaults;
use App\Http\Controllers\Controller;
use App\Http\Resources\VaultResource;
use App\Models\TemplateVersion;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VaultController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $vaults = $request->user()->account->vaults()
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => VaultResource::collection($vaults->items())->resolve(),
            'meta' => [
                'page' => $vaults->currentPage(),
                'per_page' => $vaults->perPage(),
                'total' => $vaults->total(),
            ],
        ]);
    }

    public function show(Request $request, string $vault): JsonResponse
    {
        return new VaultResource($this->find($request, $vault))->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())],
        ], $this->decisionRules($request)));

        try {
            $vault = new CreateVault(
                user: $request->user(),
                name: $validated['name'],
                description: $validated['description'] ?? null,
                colour: $validated['colour'] ?? null,
            )->execute();
        } catch (TooManyVaults $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        }

        $this->settleTheTwoDecisions($request, $vault);

        return new VaultResource($vault->refresh())->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault): JsonResponse
    {
        $vault = $this->find($request, $vault);

        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['present', 'nullable', 'string', 'max:65535'],
            'colour' => ['required', 'string', Rule::in(VaultColourEnum::values())],
        ], $this->decisionRules($request)));

        new UpdateVault(
            user: $request->user(),
            vault: $vault,
            name: $validated['name'],
            description: $validated['description'],
            colour: $validated['colour'],
        )->execute();

        $this->settleTheTwoDecisions($request, $vault);

        return new VaultResource($vault->refresh())->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $vault): Response
    {
        new DestroyVault(
            user: $request->user(),
            vault: $this->find($request, $vault),
        )->execute();

        return response()->noContent(204);
    }

    /** @return array<string, array<int, mixed>> */
    private function decisionRules(Request $request): array
    {
        return [
            'is_default' => ['nullable', 'boolean'],
            'template_version_id' => [
                'nullable',
                'integer',
                Rule::exists('templates', 'published_version_id')
                    ->where('account_id', $request->user()->account_id)
                    ->where('applies_to', TemplateAppliesToEnum::Contact->value)
                    ->where('is_active', true),
            ],
        ];
    }

    private function settleTheTwoDecisions(Request $request, Vault $vault): void
    {
        if ($request->filled('template_version_id')) {
            new SetVaultDefaultTemplate(
                user: $request->user(),
                vault: $vault,
                templateVersion: TemplateVersion::query()->findOrFail($request->integer('template_version_id')),
            )->execute();
        }

        if ($request->boolean('is_default')) {
            new SetDefaultVault(user: $request->user(), vault: $vault)->execute();
        }
    }

    private function find(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }
}
