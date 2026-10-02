<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\ViewModels\Vaults\SearchViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request, Vault $vault): View
    {
        $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'in:vault,account'],
            'group' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return view('app.vaults.search.index', [
            'viewModel' => new SearchViewModel(
                user: $request->user(),
                vault: $vault,
                query: (string) $request->string('query'),
                acrossAccount: (string) $request->string('scope') === 'account',
                group: $request->filled('group') ? (string) $request->string('group') : null,
                page: $request->integer('page', 1),
            ),
        ]);
    }
}
