<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionLoansViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PossessionLoanReturnController extends Controller
{
    public function new(Request $request, Vault $vault, Possession $possession, PossessionLoan $possessionLoan): View
    {
        abort_unless($possessionLoan->possession_id === $possession->id, 404);

        return view('app.vaults.possessions.loans', [
            'viewModel' => new PossessionLoansViewModel(
                user: $request->user(),
                possession: $possession,
                editing: $possessionLoan,
                isReturning: true,
            ),
        ]);
    }
}
