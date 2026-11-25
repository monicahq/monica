<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\ChangeContactCategories;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ContactCategoryController extends Controller
{
    public function update(Request $request, Vault $vault): RedirectResponse
    {
        $validated = $request->validate([
            'contacts' => ['required', 'array', 'min:1', 'max:'.ChangeContactCategories::LIMIT],
            'contacts.*.contact' => ['required', 'string', 'uuid'],
            'contacts.*.categories' => ['nullable', 'array'],
            'contacts.*.categories.*' => ['integer'],
            'restores' => ['nullable', 'boolean'],
        ]);

        $uuids = array_values(array_unique(array_column($validated['contacts'], 'contact')));
        $contacts = Contact::query()->withTrashed()
            ->where('vault_id', $vault->id)
            ->whereIn('uuid', $uuids)
            ->get()
            ->keyBy('uuid');

        abort_unless($contacts->count() === count($uuids), 404);

        $itemIds = collect($validated['contacts'])->pluck('categories')->flatten()->filter()->map(fn (int|string $id): int => (int) $id)->unique()->values();
        $items = ValueListItem::query()
            ->where('account_id', $request->user()->account_id)
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        abort_unless($items->count() === $itemIds->count(), 404);

        try {
            $outcome = new ChangeContactCategories(
                user: $request->user(),
                vault: $vault,
                assignments: array_map(fn (array $assignment): array => [
                    'contact' => $contacts[$assignment['contact']],
                    'categories' => array_values(array_map(fn (int|string $id): ValueListItem => $items[(int) $id], $assignment['categories'] ?? [])),
                ], array_values($validated['contacts'])),
            )->execute();
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['contacts' => $e->getMessage()]);
        }

        if ((bool) ($validated['restores'] ?? false)) {
            return redirect()
                ->route('contacts.index', $vault)
                ->with('status', trans_choice('The categories of :count person are back as they were.|The categories of :count people are back as they were.', count($outcome['changed'])));
        }

        $chosen = $validated['contacts'][array_key_first($validated['contacts'])]['categories'] ?? [];

        return redirect()
            ->route('contacts.index', $vault)
            ->with('contactCategoryChange', [
                'category' => $chosen === [] ? null : $items[(int) $chosen[0]]->label,
                'changed' => count($outcome['changed']),
                'unchanged' => collect($outcome['unchanged'])->pluck('reason')->all(),
                'previous' => $outcome['previous'],
            ]);
    }
}
