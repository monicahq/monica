<?php

namespace App\Domains\Settings\CancelAccount\Web\Controllers;

use App\Domains\Settings\CancelAccount\Services\CancelAccount;
use App\Domains\Settings\CancelAccount\Web\ViewHelpers\CancelAccountViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class CancelAccountController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/CancelAccount/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => CancelAccountViewHelper::data(),
        ]);
    }

    public function destroy(Request $request)
    {
        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            throw new ModelNotFoundException('The password is not valid.');
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
        ];

        CancelAccount::dispatch($data);

        return response()->json([
            'data' => route('login'),
        ], 200);
    }
}
