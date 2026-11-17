<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\McpDocsViewModel;
use Illuminate\View\View;

class McpDocsController extends Controller
{
    public function index(): View
    {
        return view('app.docs.mcp', [
            'viewModel' => new McpDocsViewModel,
        ]);
    }

    public function show(string $family): View
    {
        $viewModel = new McpDocsViewModel;

        abort_if($viewModel->family($family) === null, 404);

        return view('app.docs.mcp-tools', [
            'viewModel' => $viewModel,
            'family' => $viewModel->family($family),
        ]);
    }
}
