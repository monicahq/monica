<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\ViewModels\Vaults\TimelineContactCandidatesViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VaultTimelineContactCandidateController extends Controller
{
    public function index(Request $request, Vault $vault): View
    {
        return view('app.vaults.timeline-contact-candidates', [
            'viewModel' => new TimelineContactCandidatesViewModel(
                user: $request->user(),
                vault: $vault,
                query: $request->string('query')->limit(255, '')->toString(),
            ),
        ]);
    }
}
