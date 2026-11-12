<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionLoanContactCandidatesViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PossessionLoanContactCandidateController extends Controller
{
    public function index(Request $request, Vault $vault, Possession $possession): View
    {
        return view('app.vaults.possessions.loan-contact-candidates', [
            'viewModel' => new PossessionLoanContactCandidatesViewModel(
                user: $request->user(),
                vault: $vault,
                query: $request->string('query')->limit(255, '')->toString(),
            ),
        ]);
    }
}
