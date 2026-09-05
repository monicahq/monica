<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('auth.login.new');
        }

        $hasAVault = Vault::query()->where('account_id', $user->account_id)->exists();

        if ($hasAVault) {
            return redirect()->route('vaults.index');
        }

        return redirect()->route('gettingStarted.index');
    }
}
