<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\UpdateContactListColumns;
use App\Enums\ContactListColumnSourceEnum;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactListColumnsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ContactListColumnController extends Controller
{
    public function edit(Request $request, Vault $vault): View
    {
        return view('app.vaults.contact-list-columns.edit', [
            'viewModel' => new ContactListColumnsViewModel(
                user: $request->user(),
                vault: $vault,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault): RedirectResponse
    {
        $validated = $request->validate([
            'columns' => ['nullable', 'array'],
            'columns.*.source' => ['required', 'string', Rule::enum(ContactListColumnSourceEnum::class)],
            'columns.*.section' => ['nullable', 'required_if:columns.*.source,field', 'uuid'],
            'columns.*.field_key' => ['nullable', 'required_if:columns.*.source,field', 'string', 'max:64'],
            'columns.*.heading' => ['nullable', 'string', 'max:255'],
            'columns.*.is_visible' => ['required', 'boolean'],
        ]);

        try {
            new UpdateContactListColumns(
                user: $request->user(),
                vault: $vault,
                columns: array_map(fn (array $column): array => [
                    'source' => $column['source'],
                    'section' => $column['section'] ?? null,
                    'field_key' => $column['field_key'] ?? null,
                    'heading' => $column['heading'] ?? null,
                    'is_visible' => (bool) $column['is_visible'],
                ], array_values($validated['columns'] ?? [])),
            )->execute();
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['columns' => $e->getMessage()])->withInput();
        }

        return redirect()->route('contacts.index', $vault)
            ->with('status', __('The list now shows your columns.'));
    }
}
