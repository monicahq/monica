<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactFieldRowsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactFieldRowController extends Controller
{
    public function index(Request $request, Vault $vault, Contact $contact, Field $field): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($field->type->isRepeater(), 404);

        return view('app.vaults.contacts.field-rows', [
            'viewModel' => new ContactFieldRowsViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                field: $field,
                page: max(1, $request->integer('page', 1)),
            ),
        ]);
    }
}
