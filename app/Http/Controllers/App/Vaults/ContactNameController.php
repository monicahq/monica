<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\UpdateContactName;
use App\Enums\NameOrderEnum;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The one thing about a contact the product imposes rather than the template.
 *
 * It is written away from the fields because it is not one of them: every other
 * question on a record comes from a structure somebody chose, and this one is
 * there whatever they choose. Sorting, searching, headers and initials are all
 * built from it.
 *
 * The form itself is drawn by {@see ContactController::edit()}, beside the only
 * other thing on a record the template has no say over. This is only the write,
 * which is its own controller because it is its own thing being written.
 *
 * The contact representing the user is renamed like any other. What it cannot
 * do is disappear.
 */
class ContactNameController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validate([
            'prefix' => ['nullable', 'string', 'max:50'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'maiden_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'name_order' => ['nullable', Rule::enum(NameOrderEnum::class)],
        ]);

        new UpdateContactName(
            user: $request->user(),
            contact: $contact,
            prefix: $validated['prefix'] ?? null,
            firstName: $validated['first_name'] ?? null,
            middleName: $validated['middle_name'] ?? null,
            lastName: $validated['last_name'] ?? null,
            suffix: $validated['suffix'] ?? null,
            maidenName: $validated['maiden_name'] ?? null,
            nickname: $validated['nickname'] ?? null,
            nameOrder: isset($validated['name_order']) ? NameOrderEnum::from($validated['name_order']) : null,
        )->execute();

        return redirect()
            ->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('Saved.'))
            ->with('status_description', __('They are called :name now.', ['name' => $contact->refresh()->name]));
    }
}
