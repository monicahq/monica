<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\ReloadStripeCatalogue;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StripeCatalogueReloadController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $isAvailable = new ReloadStripeCatalogue(administrator: $request->user())->execute();

        if (! $isAvailable) {
            return redirect()->route('admin.billing.index')->with('error', __('The catalogue could not be read.'));
        }

        return redirect()->route('admin.billing.index')->with('status', __('The catalogue was read again.'));
    }
}
