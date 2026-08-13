<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use App\ViewModels\Vaults\SetAsideValuesViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * What a record still holds for questions its structure has stopped asking.
 * Its own screen because it is about what is not on the record, and its own
 * controller because it is not one of the things somebody does to a contact.
 */
class SetAsideValueController extends Controller
{
    public function index(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.contacts.set-aside', [
            'viewModel' => new SetAsideValuesViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
            ),
        ]);
    }
}
