<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\McpDocsViewModel;
use Illuminate\Http\Response;

class McpDocsMarkdownController extends Controller
{
    public function index(): Response
    {
        return response(new McpDocsViewModel()->markdown(), 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }
}
