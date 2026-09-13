<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Vault;
use App\ViewModels\Vaults\AbsorbedContactViewModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Where the address of a contact that a merge absorbed lands. Route binding no longer finds a soft
 * deleted contact, so this says who they became and offers the way through, rather than sending the
 * visitor to somebody they did not ask for. Anything else is still not found.
 *
 * It answers with a response rather than a view because it is reached from the route's `missing`
 * callback, whose return value goes straight down the middleware pipeline.
 */
class AbsorbedContactController extends Controller
{
    public function show(Request $request): Response
    {
        $vault = $request->route('vault');
        $address = $request->route('contact');

        abort_unless($vault instanceof Vault && is_string($address), 404);

        $absorbed = Contact::withTrashed()
            ->where('account_id', $request->user()->account_id)
            ->where('vault_id', $vault->id)
            ->addressedBy($address)
            ->first();

        abort_if($absorbed === null, 404);

        $merge = ContactMerge::query()
            ->where('merged_contact_id', $absorbed->id)
            ->whereNull('undone_at')
            ->latest('id')
            ->first();

        abort_if($merge === null, 404);

        return response()->view('app.vaults.contacts.absorbed', [
            'viewModel' => new AbsorbedContactViewModel(
                user: $request->user(),
                vault: $vault,
                absorbed: $absorbed,
                kept: $merge->keptContact,
            ),
        ]);
    }
}
