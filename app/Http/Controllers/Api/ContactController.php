<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateContact;
use App\Actions\DestroyContact;
use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\NameOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Exceptions\CannotDeleteTheMeContact;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\TemplateVersion;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $validated = $request->validate([
            'filter' => ['nullable', 'string', Rule::in(ContactGroupingEnum::values())],
            'query' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(ContactSortEnum::values())],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $contacts = Contact::query()
            ->where('vault_id', $vault->id)
            ->inGrouping(ContactGroupingEnum::fromValueOrEveryone($validated['filter'] ?? null))
            ->matchingSearch($validated['query'] ?? '')
            ->sortedBy(
                ContactSortEnum::fromValueOrName($validated['sort'] ?? null),
                $validated['direction'] ?? 'asc',
            )
            ->with(['vault', 'templateVersion.template', 'avatar'])
            ->paginate($perPage);

        return response()->json([
            'data' => ContactResource::collection($contacts->items())->resolve(),
            'meta' => [
                'page' => $contacts->currentPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    public function show(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        return new ContactResource($this->contact($vault, $contact))->response()->setStatusCode(200);
    }

    public function create(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $validated = $request->validate([
            'prefix' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'maiden_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'name_order' => ['nullable', Rule::enum(NameOrderEnum::class)],
            'is_partial' => ['nullable', 'boolean'],
            'is_favourite' => ['nullable', 'boolean'],
            'template_version_id' => [
                'nullable',
                'integer',
                Rule::exists('templates', 'published_version_id')
                    ->where('account_id', $request->user()->account_id)
                    ->where('applies_to', TemplateAppliesToEnum::Contact->value)
                    ->where('is_active', true),
            ],
        ]);

        $contact = new CreateContact(
            user: $request->user(),
            vault: $vault,
            templateVersion: $request->filled('template_version_id')
                ? TemplateVersion::query()->find($request->integer('template_version_id'))
                : null,
            prefix: $validated['prefix'] ?? null,
            firstName: $validated['first_name'],
            middleName: $validated['middle_name'] ?? null,
            lastName: $validated['last_name'] ?? null,
            suffix: $validated['suffix'] ?? null,
            maidenName: $validated['maiden_name'] ?? null,
            nickname: $validated['nickname'] ?? null,
            nameOrder: isset($validated['name_order']) ? NameOrderEnum::from($validated['name_order']) : null,
            isPartial: $request->boolean('is_partial'),
            isFavourite: $request->boolean('is_favourite'),
        )->execute();

        return new ContactResource($this->contact($vault, $contact->uuid))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $vault, string $contact): Response
    {
        $vault = $this->vault($request, $vault);

        try {
            new DestroyContact(
                user: $request->user(),
                contact: $this->contact($vault, $contact),
            )->execute();
        } catch (CannotDeleteTheMeContact $e) {
            throw ValidationException::withMessages(['contact' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function vault(Request $request, string $uuid): Vault
    {
        return $request->user()->account->vaults()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function contact(Vault $vault, string $uuid): Contact
    {
        return Contact::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->with(['vault', 'templateVersion.template', 'avatar'])
            ->firstOrFail();
    }
}
