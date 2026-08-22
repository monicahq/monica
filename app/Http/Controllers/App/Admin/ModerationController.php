<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListingVersion;
use App\ViewModels\Admin\ModerationQueueViewModel;
use App\ViewModels\Admin\ModerationSubmissionViewModel;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(): View
    {
        return view('app.admin.moderation.index', [
            'viewModel' => new ModerationQueueViewModel,
        ]);
    }

    public function show(MarketplaceListingVersion $version): View
    {
        return view('app.admin.moderation.show', [
            'viewModel' => new ModerationSubmissionViewModel(
                version: $version,
            ),
        ]);
    }
}
