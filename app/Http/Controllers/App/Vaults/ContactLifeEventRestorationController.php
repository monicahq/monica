<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\RestoreLifeEvent;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactLifeEventRestorationController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact, string $lifeEventUuid): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $lifeEvent = LifeEvent::onlyTrashed()
            ->where('account_id', $request->user()->account_id)
            ->where('vault_id', $vault->id)
            ->where('uuid', $lifeEventUuid)
            ->firstOrFail();

        new RestoreLifeEvent(
            user: $request->user(),
            vault: $vault,
            contact: $contact,
            lifeEvent: $lifeEvent,
        )->execute();

        return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __(':type is back on the timeline.', ['type' => $lifeEvent->lifeEventType->name]))
            ->with('lifeEventJustAdded', $lifeEvent->id);
    }
}
