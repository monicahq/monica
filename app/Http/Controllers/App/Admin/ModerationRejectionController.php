<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\RejectMarketplaceListingVersion;
use App\Exceptions\ListingIsNotAwaitingModeration;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceListingVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModerationRejectionController extends Controller
{
    public function create(Request $request, MarketplaceListingVersion $version): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:65535'],
        ]);

        try {
            new RejectMarketplaceListingVersion(
                version: $version,
                administrator: $request->user(),
                reason: $validated['reason'],
            )->execute();
        } catch (ListingIsNotAwaitingModeration $exception) {
            return back()->withErrors(['decision' => $exception->getMessage()]);
        }

        return redirect()->route('admin.moderation.index')
            ->with('status', __('It has been refused.'))
            ->with('status_description', __('The author has been sent the reason, and can submit a new version whenever they are ready.'));
    }
}
