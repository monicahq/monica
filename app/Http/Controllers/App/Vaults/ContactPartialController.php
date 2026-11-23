<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DemoteFullContact;
use App\Actions\PromotePartialContact;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ContactPartialController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $request->validate(['partial' => ['required', 'boolean']]);

        try {
            if ($request->boolean('partial')) {
                new DemoteFullContact(user: $request->user(), contact: $contact)->execute();
            }

            if (! $request->boolean('partial')) {
                new PromotePartialContact(user: $request->user(), contact: $contact)->execute();
            }
        } catch (AuthorizationException|InvalidArgumentException $e) {
            return back()->withErrors(['partial' => $e->getMessage()]);
        }

        $destination = redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact]);

        if ($request->boolean('partial')) {
            return $destination
                ->with('status', __(':name is a partial contact.', ['name' => $contact->name]))
                ->with('status_description', __('They are under Partial contacts in the list of people.'));
        }

        return $destination
            ->with('status', __(':name is a full contact.', ['name' => $contact->name]))
            ->with('status_description', __('They are on the list of people with everybody else.'));
    }
}
