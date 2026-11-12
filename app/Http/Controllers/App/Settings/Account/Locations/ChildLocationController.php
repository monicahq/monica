<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Locations;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\ViewModels\Settings\Account\Locations\LocationsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildLocationController extends Controller
{
    public function new(Request $request, Location $location): View
    {
        return view('app.settings.account.locations.index', [
            'viewModel' => new LocationsViewModel(
                user: $request->user(),
                creating: true,
                parent: $location,
            ),
        ]);
    }
}
