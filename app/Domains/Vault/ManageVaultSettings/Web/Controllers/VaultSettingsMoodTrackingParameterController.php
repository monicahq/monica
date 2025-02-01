<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\CreateMoodTrackingParameter;
use App\Domains\Vault\ManageVaultSettings\Services\DestroyMoodTrackingParameter;
use App\Domains\Vault\ManageVaultSettings\Services\UpdateMoodTrackingParameter;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsMoodTrackingParameterController extends Controller
{
    public function store(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'label' => $request->input('label'),
            'hex_color' => $request->input('hex_color'),
        ];

        $parameter = (new CreateMoodTrackingParameter)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoMoodTrackingParameter($parameter),
        ], 201);
    }

    public function update(Request $request, string $vaultId, int $moodTrackingParameterId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'mood_tracking_parameter_id' => $moodTrackingParameterId,
            'label' => $request->input('label'),
            'hex_color' => $request->input('hex_color'),
        ];

        $parameter = (new UpdateMoodTrackingParameter)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoMoodTrackingParameter($parameter),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $moodTrackingParameterId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'mood_tracking_parameter_id' => $moodTrackingParameterId,
        ];

        (new DestroyMoodTrackingParameter)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
