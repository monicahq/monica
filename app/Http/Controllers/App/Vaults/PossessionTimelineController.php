<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Enums\PossessionTimelineSectionEnum;
use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionTimelineViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PossessionTimelineController extends Controller
{
    public function index(Request $request, Vault $vault, Possession $possession, ?string $section = null): View
    {
        return view('app.vaults.possessions.timeline', [
            'viewModel' => new PossessionTimelineViewModel(
                user: $request->user(),
                possession: $possession,
                section: $section === null ? null : PossessionTimelineSectionEnum::from($section),
            ),
        ]);
    }
}
