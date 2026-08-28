<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\ApiDocsViewModel;
use Illuminate\Http\Response;

/**
 * The same reference, as plain text, for a reader that is not a browser.
 *
 * Whole at /docs/api.md, one endpoint at a time at /docs/api/{section}.md. It
 * is the same source either way, so the text can never say something the page
 * does not.
 */
class ApiDocsMarkdownController extends Controller
{
    public function index(): Response
    {
        return $this->markdown(new ApiDocsViewModel()->markdown());
    }

    public function show(string $section): Response
    {
        $viewModel = new ApiDocsViewModel;
        $found = $viewModel->section($section);

        abort_if($found === null, 404);

        return $this->markdown($viewModel->markdown($found));
    }

    private function markdown(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }
}
