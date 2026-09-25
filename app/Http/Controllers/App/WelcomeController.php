<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\MarkUserAsWelcomed;
use App\Http\Controllers\Controller;
use App\ViewModels\Welcome\WelcomeViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.welcome.index', [
            'viewModel' => new WelcomeViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        new MarkUserAsWelcomed(
            user: $request->user(),
        )->execute();

        return redirect()->route('home.index');
    }
}
