<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Transfer;

use App\Actions\CreateAccountExport;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountExportController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vault' => ['nullable', 'integer'],
        ]);

        try {
            new CreateAccountExport(
                user: $request->user(),
                vaultIds: ($validated['vault'] ?? null) === null ? null : [(int) $validated['vault']],
            )->execute();
        } catch (AuthorizationException $exception) {
            return redirect()->route('settings.accountImports.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('settings.accountImports.index')
            ->with('status', __('The export is being prepared. You will get an email when it is ready.'));
    }
}
