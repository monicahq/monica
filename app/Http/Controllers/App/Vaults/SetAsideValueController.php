<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use App\ViewModels\Vaults\SetAsideValuesViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
