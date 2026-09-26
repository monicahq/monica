<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\DocsViewModel;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function index(): View
    {
        return view('app.docs.index', [
            'viewModel' => new DocsViewModel,
        ]);
    }
}
