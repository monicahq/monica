<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\Vault;
use App\ViewModels\Vaults\LifeEventFormBlock;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactLifeEventFieldController extends Controller
{
    public function index(Request $request, Vault $vault, Contact $contact, LifeEventType $lifeEventType): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $lifeEvent = $request->filled('lifeEvent')
            ? LifeEvent::query()->where('vault_id', $vault->id)->where('uuid', (string) $request->string('lifeEvent'))->firstOrFail()
            : null;

        $form = new LifeEventFormBlock(user: $request->user(), vault: $vault, contact: $contact, lifeEvent: $lifeEvent);

        return view('app.vaults.contacts._life-event-fields', [
            'lifeEventType' => $lifeEventType,
            'fields' => $form->fieldsOf($lifeEventType),
        ]);
    }
}
