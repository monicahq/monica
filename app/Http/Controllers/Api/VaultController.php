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
use App\Http\Controllers\Controller;
use App\Http\Resources\VaultResource;
use App\Models\TemplateVersion;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * The compartments an account holds. Nothing can be recorded until one exists,
 * so this is the endpoint the rest of the API stands on.
 *
 * A vault of another account is not found rather than refused, because the
 * compartment is the whole point: answering "you may not" would already say the
 * vault exists. The address is the uuid, so a vault is looked up by it and by
 * nothing else, and a sequential number reaches nothing.
 */
class VaultController extends Controller
{
    /**
     * The account's vaults, in the order the account arranged them.
     *
     * Not newest first, which is how the other lists here answer. A vault
     * carries the place its owner put it in, and that is the order the switcher
     * and the vault list show, so answering any other way would hand a caller a
     * different list from the one a person sees. The id breaks a tie, so the
     * order is total and a page boundary cannot drop or repeat a vault.
     *
     * The page is shaped by hand for the same reason as everywhere else in this
     * API: the reference promises `page`, `per_page` and `total`, and nothing
     * more.
     */
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

    /**
     * The vault goes at the end of the account's list. If the account held none,
     * this one becomes the vault records land in by default, whether or not it
     * was asked for, because exactly one vault has to be that and there is
     * nothing else to be it.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())],
        ], $this->decisionRules($request)));

        $vault = new CreateVault(
            user: $request->user(),
            name: $validated['name'],
            description: $validated['description'] ?? null,
            colour: $validated['colour'] ?? null,
        )->execute();

        $this->settleTheTwoDecisions($request, $vault);

        return new VaultResource($vault->refresh())->response()->setStatusCode(201);
    }

    /**
     * The wording of the vault, and nothing else about it. What it holds, where
     * it sits in the list and whether it is the default one are not touched.
     *
     * This replaces rather than patches, which is why all three fields are
     * demanded. The operation underneath writes a field it is not given as empty,
     * so a caller sending only a new name would reset the colour and erase what
     * the vault is for. Nothing may disappear without being said, so `colour` is
     * required and `description` has to be stated even when the answer is null.
     */
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

    /**
     * Delete a vault and everything it separates from the rest. It is the most
     * destructive call in this API, short of deleting the account.
     *
     * There is no second step. A screen has to ask before it destroys something,
     * and this is not a screen: the call is itself the confirmation, so the
     * reference says what goes rather than asking again. The contacts and the
     * records of the invented kinds leave with the vault, and every private link
     * into the compartment stops opening, permanently.
     *
     * The deletion is soft, so nothing is destroyed, but no endpoint brings a
     * vault back today. A caller should treat this as final.
     */
    public function destroy(Request $request, string $vault): Response
    {
        new DestroyVault(
            user: $request->user(),
            vault: $this->find($request, $vault),
        )->execute();

        return response()->noContent(204);
    }

    /**
     * The two things a request may settle that are decisions about the account
     * rather than wording of the vault.
     *
     * The structure is asked of the templates table rather than trusted, which is
     * the rule the form behind the browser uses, so a version that is a draft, or
     * belongs to somebody else, or describes something other than a contact,
     * cannot be named here.
     *
     * @return array<string, array<int, mixed>>
     */
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

    /**
     * Settle them, in the order the browser settles them, each through the
     * operation that owns it so that each is written to the journal on its own.
     *
     * A request saying the vault is not the default, or saying nothing about it,
     * changes nothing: exactly one vault per account holds the title while the
     * account holds a vault, so there would be nothing to hand it to.
     */
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

    /**
     * The vault the address names, within the caller's account.
     *
     * Scoping the query is what makes the compartment hold, and letting it throw
     * is what makes another account's vault indistinguishable from one that never
     * existed.
     */
    private function find(Request $request, string $uuid): Vault
    {
        return $request->user()->account->vaults()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
