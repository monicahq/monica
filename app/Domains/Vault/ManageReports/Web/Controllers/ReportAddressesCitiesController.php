<?php

namespace App\Domains\Vault\ManageReports\Web\Controllers;

use App\Domains\Vault\ManageReports\Web\ViewHelpers\ReportCitiesShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Inertia\Inertia;

use function Safe\mb_convert_encoding;

class ReportAddressesCitiesController extends Controller
{
    public function show(Request $request, string $vaultId, string $city)
    {
        $vault = Vault::findOrFail($vaultId);
        $city = mb_convert_encoding(urldecode($city), 'UTF-8');

        return Inertia::render('Vault/Reports/Address/Cities/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ReportCitiesShowViewHelper::data($vault, $city),
        ]);
    }
}
