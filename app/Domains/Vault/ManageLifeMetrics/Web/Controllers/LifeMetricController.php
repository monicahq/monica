<?php

namespace App\Domains\Vault\ManageLifeMetrics\Web\Controllers;

use App\Domains\Vault\ManageLifeMetrics\Services\CreateLifeMetric;
use App\Domains\Vault\ManageLifeMetrics\Services\DestroyLifeMetric;
use App\Domains\Vault\ManageLifeMetrics\Services\UpdateLifeMetric;
use App\Domains\Vault\ManageLifeMetrics\Web\ViewHelpers\VaultLifeMetricsViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LifeMetricController extends Controller
{
    public function store(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'label' => $request->input('label'),
        ];

        $lifeMetric = (new CreateLifeMetric)->execute($data);

        $vault = Vault::find($vaultId);
        $contact = Auth::user()->getContactInVault($vault);

        return response()->json([
            'data' => VaultLifeMetricsViewHelper::dto($lifeMetric, Carbon::now()->year, $contact),
        ], 201);
    }

    public function update(Request $request, string $vaultId, int $lifeMetricId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'life_metric_id' => $lifeMetricId,
            'label' => $request->input('label'),
        ];

        $lifeMetric = (new UpdateLifeMetric)->execute($data);
        $vault = Vault::find($vaultId);
        $contact = Auth::user()->getContactInVault($vault);

        return response()->json([
            'data' => VaultLifeMetricsViewHelper::dto($lifeMetric, Carbon::now()->year, $contact),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $lifeMetricId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'life_metric_id' => $lifeMetricId,
        ];

        (new DestroyLifeMetric)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
