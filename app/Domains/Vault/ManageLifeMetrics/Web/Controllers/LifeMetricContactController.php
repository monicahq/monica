<?php

namespace App\Domains\Vault\ManageLifeMetrics\Web\Controllers;

use App\Domains\Vault\ManageLifeMetrics\Services\IncrementLifeMetric;
use App\Domains\Vault\ManageLifeMetrics\Web\ViewHelpers\VaultLifeMetricsViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LifeMetricContactController extends Controller
{
    public function store(Request $request, string $vaultId, int $lifeMetricId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'life_metric_id' => $lifeMetricId,
        ];

        $lifeMetric = (new IncrementLifeMetric)->execute($data);

        $vault = Vault::find($vaultId);
        $contact = Auth::user()->getContactInVault($vault);

        return response()->json([
            'data' => VaultLifeMetricsViewHelper::dto($lifeMetric, Carbon::now()->year, $contact),
        ], 201);
    }
}
