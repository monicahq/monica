<?php

namespace App\Domains\BlackBookd\Web\Controllers;

use App\Domains\BlackBookd\ManageSettings\Services\UpdateBlackBookdAccountSettings;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BlackBookdSettingsController extends Controller
{
    public function index()
    {
        $this->authorize('administrator');
        $account = Auth::user()->account;

        return Inertia::render('Settings/BlackBookd/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => [
                'blackbookd_enabled' => $account->blackbookd_enabled,
                'blackbookd_blur_dashboard' => $account->blackbookd_blur_dashboard,
                'blackbookd_partner_lookback_days' => $account->blackbookd_partner_lookback_days,
                'url' => [
                    'update' => route('settings.blackbookd.update'),
                    'export' => route('settings.blackbookd.export'),
                ],
            ],
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('administrator');
        (new UpdateBlackBookdAccountSettings)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'blackbookd_enabled' => $request->boolean('blackbookd_enabled'),
            'blackbookd_blur_dashboard' => $request->boolean('blackbookd_blur_dashboard'),
            'blackbookd_partner_lookback_days' => $request->integer('blackbookd_partner_lookback_days', 90),
        ]);

        return redirect()->route('settings.blackbookd.index');
    }

    public function export()
    {
        $this->authorize('administrator');
        abort_unless(Auth::user()->account->blackbookd_enabled, 403);

        $vaultIds = Auth::user()->account->vaults()->pluck('id');
        $payload = [
            'exported_at' => now()->toIso8601String(),
            'product' => 'BlackBookd',
            'vault_ids' => $vaultIds,
        ];

        return response()->streamDownload(function () use ($vaultIds) {
            echo json_encode([
                'encounters' => \App\Models\BlackBookdEncounter::whereIn('vault_id', $vaultIds)->get(),
                'health_log' => \App\Models\BlackBookdHealthLogEntry::whereIn('vault_id', $vaultIds)->get(),
                'dating_pipelines' => \App\Models\BlackBookdDatingPipeline::whereIn('vault_id', $vaultIds)->get(),
            ], JSON_PRETTY_PRINT);
        }, 'blackbookd-export-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']);
    }
}