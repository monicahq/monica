<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The front door. An account holding nothing goes to the screen that explains
 * what to do first, one holding a vault goes to its vaults, and somebody who
 * is not signed in gets the public page.
 */
class HomeController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return view('welcome');
        }

        $hasAVault = Vault::query()->where('account_id', $user->account_id)->exists();

        if ($hasAVault) {
            return redirect()->route('vaults.index');
        }

        return redirect()->route('gettingStarted.index');
    }
}
