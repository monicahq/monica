<?php

namespace App\Domains\BlackBookd\Api\Controllers;

use App\Domains\BlackBookd\ManageSettings\Services\UpdateBlackBookdAccountSettings;
use App\Http\Controllers\ApiController;
use App\Models\BlackBookdContactProfile;
use App\Models\BlackBookdDatingPipeline;
use App\Models\BlackBookdEncounter;
use App\Models\BlackBookdHealthLogEntry;
use Illuminate\Http\Request;

class BlackBookdSettingsController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only(['show', 'export']);
        $this->middleware('abilities:write')->only('update');
        parent::__construct();
    }

    public function show(Request $request)
    {
        $account = $request->user()->account;

        return response()->json([
            'data' => [
                'blackbookd_enabled' => $account->blackbookd_enabled,
                'blackbookd_blur_dashboard' => $account->blackbookd_blur_dashboard,
                'blackbookd_partner_lookback_days' => $account->blackbookd_partner_lookback_days,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $account = (new UpdateBlackBookdAccountSettings)->execute([
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'blackbookd_enabled' => $request->boolean('blackbookd_enabled'),
            'blackbookd_blur_dashboard' => $request->boolean('blackbookd_blur_dashboard'),
            'blackbookd_partner_lookback_days' => $request->integer('blackbookd_partner_lookback_days', 90),
        ]);

        return response()->json(['data' => [
            'blackbookd_enabled' => $account->blackbookd_enabled,
            'blackbookd_blur_dashboard' => $account->blackbookd_blur_dashboard,
            'blackbookd_partner_lookback_days' => $account->blackbookd_partner_lookback_days,
        ]]);
    }

    public function export(Request $request)
    {
        if (! $request->user()->account->blackbookd_enabled) {
            return response()->json(['error' => 'BlackBookd module disabled'], 403);
        }

        $vaultIds = $request->user()->account->vaults()->pluck('id');

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'encounters' => BlackBookdEncounter::whereIn('vault_id', $vaultIds)->with('contacts:id')->get(),
            'contact_profiles' => BlackBookdContactProfile::whereHas('contact', fn ($q) => $q->whereIn('vault_id', $vaultIds))->get(),
            'health_log' => BlackBookdHealthLogEntry::whereIn('vault_id', $vaultIds)->get(),
            'dating_pipelines' => BlackBookdDatingPipeline::whereIn('vault_id', $vaultIds)->get(),
        ]);
    }
}