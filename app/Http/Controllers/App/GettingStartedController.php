<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\ViewModels\GettingStarted\GettingStartedViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GettingStartedController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.getting-started.index', [
            'viewModel' => new GettingStartedViewModel(
                user: $request->user(),
            ),
        ]);
    }
}
