<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupMemberCandidatesViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupMemberCandidateController extends Controller
{
    public function index(Request $request, Vault $vault, Group $group): View
    {
        return view('app.vaults.groups.member-candidates', [
            'viewModel' => new GroupMemberCandidatesViewModel(
                user: $request->user(),
                vault: $vault,
                group: $group,
                query: $request->string('query')->limit(255, '')->toString(),
            ),
        ]);
    }
}
