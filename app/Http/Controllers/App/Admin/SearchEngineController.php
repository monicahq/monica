<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\CheckSearchEngine;
use App\Http\Controllers\Controller;
use App\ViewModels\Admin\SearchEngineViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchEngineController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.admin.search.index', [
            'viewModel' => new SearchEngineViewModel(
                user: $request->user(),
                status: new CheckSearchEngine()->execute(),
            ),
        ]);
    }
}
