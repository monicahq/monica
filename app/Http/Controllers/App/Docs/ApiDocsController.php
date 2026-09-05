<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\ApiDocsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApiDocsController extends Controller
{
    public function index(): RedirectResponse
    {
        $first = new ApiDocsViewModel()->sections()[0];

        return redirect()->route('docs.api.show', ['section' => $first->id]);
    }

    public function show(string $section): View
    {
        $viewModel = new ApiDocsViewModel;
        $current = $viewModel->section($section);

        abort_if($current === null, 404);

        return view('app.docs.api.show', [
            'navigation' => $viewModel->navigation(),
            'section' => $current,
        ]);
    }
}
