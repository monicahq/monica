<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Docs;

use App\Http\Controllers\Controller;
use App\ViewModels\Docs\DocsViewModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LlmsTextController extends Controller
{
    public function index(Request $request): Response
    {
        return response(new DocsViewModel(account: $request->user()?->account)->llmsText(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
