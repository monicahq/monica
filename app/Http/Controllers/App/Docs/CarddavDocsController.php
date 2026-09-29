<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\CarddavDocsViewModel;
use Illuminate\View\View;

class CarddavDocsController extends Controller
{
    public function index(): View
    {
        return view('app.docs.carddav', ['viewModel' => new CarddavDocsViewModel]);
    }
}
