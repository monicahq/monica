<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class ContactJsonExportController extends Controller
{
    public function download(Request $request, Vault $vault, Contact $contact)
    {
        $data = ContactShowViewHelper::data($contact, Auth::user());
        $name = Str::of($contact->name)->slug(language: App::getLocale());

        return Redirect::back()->with('flash', [
            'data' => json_encode($data, JSON_PRETTY_PRINT),
            'filename' => "$name.json",
        ]);
    }
}
