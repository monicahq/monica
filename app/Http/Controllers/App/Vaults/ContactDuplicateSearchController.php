<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Jobs\DetectDuplicateContacts;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;

class ContactDuplicateSearchController extends Controller
{
    public function create(Vault $vault): RedirectResponse
    {
        if ($vault->isBeingImportedInto()) {
            return redirect()
                ->route('vaults.duplicates.index', $vault)
                ->with('status', __('The search waits for the import.'))
                ->with('status_description', __('An import is writing into this vault. Monica looks for duplicates on its own once the import has finished.'));
        }

        DetectDuplicateContacts::dispatch($vault)->onQueue('low');

        return redirect()
            ->route('vaults.duplicates.index', $vault)
            ->with('status', __('Looking for duplicates.'))
            ->with('status_description', __('This runs in the background. Come back to this screen in a moment to see what it found.'));
    }
}
