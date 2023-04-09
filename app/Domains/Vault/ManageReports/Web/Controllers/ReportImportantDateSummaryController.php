<?php

namespace App\Domains\Vault\ManageReports\Web\Controllers;

use App\Domains\Vault\ManageReports\Web\ViewHelpers\ReportImportantDateSummaryIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReportImportantDateSummaryController extends Controller
{
    public function index(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Reports/ImportantDate/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ReportImportantDateSummaryIndexViewHelper::data($vault, Auth::user()),
        ]);
    }
}
