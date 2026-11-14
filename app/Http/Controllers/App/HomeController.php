<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null && config('monica.preview_list_enabled') && ! config('monica.registration_enabled')) {
            return redirect()->route('auth.previewList.new');
        }

        if ($user === null) {
            return redirect()->route('auth.login.new');
        }

        if ($user->welcomed_at === null) {
            return redirect()->route('welcome.index');
        }

        return redirect()->route('vaults.index');
    }
}
