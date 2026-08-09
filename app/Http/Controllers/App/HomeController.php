<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The front door. Where it leads says what state the account is in: an account
 * that holds nothing is taken to the screen that explains what to do first, and
 * one that holds a vault is taken to its vaults.
 *
 * Somebody who is not signed in gets the public page, which is still the
 * scaffolding one until the marketing site is written.
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
