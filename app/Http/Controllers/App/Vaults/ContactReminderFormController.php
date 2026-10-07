<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactReminderFormController extends Controller
{
    public function new(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.contacts.show', [
            'viewModel' => new ContactViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                opensReminderForm: true,
            ),
        ]);
    }
}
