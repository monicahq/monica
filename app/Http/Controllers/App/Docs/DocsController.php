<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\DocsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.docs.index', [
            'viewModel' => new DocsViewModel(account: $request->user()?->account),
        ]);
    }
}
