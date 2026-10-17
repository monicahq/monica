<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactDuplicatesViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactDuplicateController extends Controller
{
    public function index(Request $request, Vault $vault): View
    {
        return view('app.vaults.duplicates.index', [
            'viewModel' => new ContactDuplicatesViewModel(
                user: $request->user(),
                vault: $vault,
                size: PageSize::from($request, ContactDuplicatesViewModel::PER_PAGE),
            ),
        ]);
    }
}
