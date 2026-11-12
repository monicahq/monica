<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\ViewModels\Vaults\KnowledgeViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function index(Request $request, Vault $vault): View
    {
        return view('app.vaults.knowledge.index', [
            'viewModel' => new KnowledgeViewModel(
                user: $request->user(),
                vault: $vault,
                search: $request->string('search')->limit(255, '')->toString(),
            ),
        ]);
    }
}
