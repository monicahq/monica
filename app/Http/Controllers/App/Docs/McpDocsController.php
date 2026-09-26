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
}
