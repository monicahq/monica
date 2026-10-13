<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Http\Controllers\Controller;
use App\ViewModels\Admin\FlagsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlagController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.admin.flags.index', [
            'viewModel' => new FlagsViewModel(user: $request->user()),
        ]);
    }
}
