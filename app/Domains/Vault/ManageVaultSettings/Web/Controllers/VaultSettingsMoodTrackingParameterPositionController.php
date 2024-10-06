<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateMoodTrackingParameterPosition;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsMoodTrackingParameterPositionController extends Controller
{
    public function update(Request $request, string $vaultId, int $moodTrackingParameterId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'mood_tracking_parameter_id' => $moodTrackingParameterId,
            'new_position' => $request->input('position'),
        ];

        $parameter = (new UpdateMoodTrackingParameterPosition)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoMoodTrackingParameter($parameter),
        ], 200);
    }
}
