<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\ApproveMarketplaceListingVersion;
use App\Exceptions\ListingIsNotAwaitingModeration;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceListingVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModerationApprovalController extends Controller
{
    public function create(Request $request, MarketplaceListingVersion $version): RedirectResponse
    {
        try {
            new ApproveMarketplaceListingVersion(
                version: $version,
                administrator: $request->user(),
            )->execute();
        } catch (ListingIsNotAwaitingModeration $exception) {
            return back()->withErrors(['decision' => $exception->getMessage()]);
        }

        return redirect()->route('admin.moderation.index')
            ->with('status', __('It is in the catalogue.'))
            ->with('status_description', __('The author has been told, and anybody can install it from now on.'));
    }
}
