<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DismissDuplicateCandidate;
use App\Http\Controllers\Controller;
use App\Models\ContactDuplicateCandidate;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactDuplicateDismissalController extends Controller
{
    public function update(Request $request, Vault $vault, ContactDuplicateCandidate $candidate): RedirectResponse
    {
        abort_unless($candidate->vault_id === $vault->id, 404);

        new DismissDuplicateCandidate(
            user: $request->user(),
            candidate: $candidate,
        )->execute();

        return redirect()
            ->route('vaults.duplicates.index', $vault)
            ->with('status', __('Noted: two different people.'))
            ->with('status_description', __('This pair will not be raised again.'));
    }
}
