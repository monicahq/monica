<?php

namespace App\Settings\CancelAccount\Web\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Settings\CancelAccount\Services\CancelAccount;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Settings\CancelAccount\Web\ViewHelpers\CancelAccountViewHelper;

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
            'author_id' => Auth::user()->id,
        ];

        (new CancelAccount)->execute($data);

        return response()->json([
            'data' => route('login'),
        ], 200);
    }
}
