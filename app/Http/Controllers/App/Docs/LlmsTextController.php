<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\DocsViewModel;
use Illuminate\Http\Response;

class LlmsTextController extends Controller
{
    public function index(): Response
    {
        return response(new DocsViewModel()->llmsText(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
