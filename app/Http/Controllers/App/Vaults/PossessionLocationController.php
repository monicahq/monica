<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PossessionLocationController extends Controller
{
    public function show(Request $request, Vault $vault, Location $location): View
    {
        return view('app.vaults.possessions.index', [
            'viewModel' => new PossessionsViewModel(
                user: $request->user(),
                vault: $vault,
                location: $location,
            ),
        ]);
    }
}
