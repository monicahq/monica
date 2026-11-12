<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\ApiChangelogViewModel;
use Illuminate\Http\Response;

class ApiChangelogMarkdownController extends Controller
{
    public function index(ApiChangelogViewModel $viewModel): Response
    {
        return response($viewModel->markdown(), 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    public function show(ApiChangelogViewModel $viewModel, string $entry): Response
    {
        $found = $viewModel->entry($entry);

        abort_if($found === null, 404);

        return response($viewModel->markdown($found), 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }
}
