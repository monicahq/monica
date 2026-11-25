<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DestroyContacts;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactDeletionViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactDeletionController extends Controller
{
    public function show(Request $request, Vault $vault): View
    {
        $validated = $request->validate($this->rules());

        return view('app.vaults.contacts._deletion', [
            'viewModel' => new ContactDeletionViewModel(
                user: $request->user(),
                vault: $vault,
                contacts: $this->contacts($vault, $validated['contacts']),
            ),
        ]);
    }

    public function destroy(Request $request, Vault $vault): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $outcome = new DestroyContacts(
            user: $request->user(),
            vault: $vault,
            contacts: $this->contacts($vault, $validated['contacts']),
        )->execute();

        $reasons = collect($outcome['kept'])->pluck('reason')->unique()->implode(' ');

        if ($outcome['deleted'] === [] && $outcome['kept'] === []) {
            return redirect()->route('contacts.index', $vault);
        }

        if ($outcome['deleted'] === []) {
            return redirect()
                ->route('contacts.index', $vault)
                ->with('error', $reasons);
        }

        return redirect()
            ->route('contacts.index', $vault)
            ->with('status', trans_choice(':count person is deleted.|:count people are deleted.', count($outcome['deleted'])))
            ->with('status_description', $reasons !== '' ? $reasons : __('Nothing they took part in went with them.'));
    }

    /** @return array<string, list<string>> */
    private function rules(): array
    {
        return [
            'contacts' => ['required', 'array', 'min:1', 'max:'.DestroyContacts::LIMIT],
            'contacts.*' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * @param  list<string>  $uuids
     * @return list<Contact>
     */
    private function contacts(Vault $vault, array $uuids): array
    {
        $uuids = array_values(array_unique($uuids));

        $contacts = Contact::query()->withTrashed()
            ->where('vault_id', $vault->id)
            ->whereIn('uuid', $uuids)
            ->get()
            ->sortBy(fn (Contact $contact): int|false => array_search($contact->uuid, $uuids, true))
            ->values();

        abort_unless($contacts->count() === count($uuids), 404);

        return $contacts->all();
    }
}
