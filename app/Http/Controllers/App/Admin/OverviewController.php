<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Http\Controllers\Controller;
use App\ViewModels\Admin\OverviewViewModel;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function index(): View
    {
        return view('app.admin.overview.index', [
            'viewModel' => new OverviewViewModel,
        ]);
    }
}
