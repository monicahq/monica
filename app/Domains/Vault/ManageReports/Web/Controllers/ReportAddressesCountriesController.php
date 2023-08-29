<?php

namespace App\Domains\Vault\ManageReports\Web\Controllers;

use App\Domains\Vault\ManageReports\Web\ViewHelpers\ReportCountriesShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Inertia\Inertia;

use function Safe\mb_convert_encoding;

class ReportAddressesCountriesController extends Controller
{
    public function show(Request $request, string $vaultId, string $country)
    {
        $vault = Vault::findOrFail($vaultId);
        $country = mb_convert_encoding(urldecode($country), 'UTF-8');

        return Inertia::render('Vault/Reports/Address/Countries/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ReportCountriesShowViewHelper::data($vault, $country),
        ]);
    }
}
