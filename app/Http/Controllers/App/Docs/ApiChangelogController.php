<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\ApiChangelogViewModel;
use App\ViewModels\Docs\ApiDocsViewModel;
use Illuminate\View\View;

class ApiChangelogController extends Controller
{
    public function index(ApiChangelogViewModel $viewModel): View
    {
        return view('app.docs.api.changelog.index', [
            'navigation' => new ApiDocsViewModel()->navigation(),
            'releases' => $viewModel->releases(),
            'totalLabel' => $viewModel->totalLabel(),
            'markdown' => $viewModel->markdown(),
        ]);
    }

    public function show(ApiChangelogViewModel $viewModel, string $entry): View
    {
        $found = $viewModel->entry($entry);

        abort_if($found === null, 404);

        $reference = new ApiDocsViewModel;

        return view('app.docs.api.changelog.show', [
            'navigation' => $reference->navigation(),
            'entry' => $found,
            'sameRelease' => $viewModel->sameRelease($found),
            'reference' => $reference,
            'markdown' => $viewModel->markdown($found),
        ]);
    }
}
