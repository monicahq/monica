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

/**
 * The people an account knows, read through the vault each of them lives in.
 *
 * Neither half of the address is trusted on its own: the vault has to belong to
 * the caller's account, and the contact has to be in the vault the address
 * actually names. Without the second half, the address of one vault would open
 * somebody kept in another.
 *
 * A contact of another account is not found rather than refused, because saying
 * "you may not" would already say the contact exists. Both are addressed by
 * their uuid and by nothing else, so a sequential number reaches nothing.
 */
class ContactController extends Controller
{
    /**
     * The vault's contacts, narrowed and ordered the way the people screen
     * narrows and orders them.
     *
     * The narrowing, the search and the ordering are scopes on the model rather
     * than a query written here, so this endpoint and that screen cannot come to
     * disagree about what a grouping holds or where the unknowns of a sort go.
     *
     * Not newest first, which is how the account wide lists in this API answer.
     * A caller and a person looking at the same vault should be looking at the
     * same list, and that list is in name order.
     *
     * The page is shaped by hand for the same reason as everywhere else in this
     * API: the reference promises `page`, `per_page` and `total`, and nothing
     * more.
     */
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

    /**
     * Write somebody down. A first name is all this asks for, because somebody
     * met once is worth writing down with nothing else, and a form that refuses
     * that is one people work around.
     *
     * The structure is asked of the templates table rather than trusted, which is
     * the rule the form behind the browser uses, so a version that is a draft, or
     * belongs to somebody else, or describes something other than a contact,
     * cannot be named here. Naming none at all is ordinary: the operation reads
     * the vault's default in that case.
     */
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

    /**
     * Delete a contact and leave behind everything they took part in.
     *
     * Nothing is destroyed. Relationships, activities and files outlive them
     * because they describe things that happened rather than the row itself, and
     * the reminders about them go quiet with the reason rather than disappearing.
     *
     * The contact representing the caller never goes, and that refusal is not a
     * question about the input, so it is caught here rather than in the validator
     * and answered as one the request cannot be granted.
     */
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

    /**
     * The vault the address names, within the caller's account.
     *
     * Scoping the query is what makes the compartment hold, and letting it throw
     * is what makes another account's vault indistinguishable from one that
     * never existed.
     */
    private function vault(Request $request, string $uuid): Vault
    {
        return $request->user()->account->vaults()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * The contact the address names, within the vault the address names. The
     * vault has already been proved to be the caller's, which is what makes
     * scoping to it enough.
     */
    private function contact(Vault $vault, string $uuid): Contact
    {
        return Contact::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->with(['vault', 'templateVersion.template', 'avatar'])
            ->firstOrFail();
    }
}
