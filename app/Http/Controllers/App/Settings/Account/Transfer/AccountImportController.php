<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Transfer;

use App\Actions\CancelAccountImport;
use App\Actions\CreateAccountImport;
use App\Actions\StartAccountImport;
use App\Exceptions\ImportExceedsALimit;
use App\Http\Controllers\Controller;
use App\Models\AccountImport;
use App\Models\Vault;
use App\ViewModels\Settings\Account\Transfer\AccountImportViewModel;
use App\ViewModels\Settings\Account\Transfer\AccountTransferViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class AccountImportController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.transfer.index', [
            'viewModel' => new AccountTransferViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.AccountTransferViewModel::MAXIMUM_UPLOAD_IN_KILOBYTES],
        ]);

        try {
            $import = new CreateAccountImport(
                user: $request->user(),
                file: $validated['file'],
            )->execute();
        } catch (AuthorizationException $exception) {
            return redirect()->route('settings.accountImports.index')
                ->withErrors(['file' => $exception->getMessage()]);
        }

        return redirect()->route('settings.accountImports.show', $import)
            ->with('status', __('The file is being read.'));
    }

    public function show(Request $request, AccountImport $accountImport): View
    {
        return view('app.settings.account.transfer.import', [
            'viewModel' => new AccountImportViewModel(
                user: $request->user(),
                import: $accountImport,
            ),
        ]);
    }

    public function update(Request $request, AccountImport $accountImport): RedirectResponse
    {
        $validated = $request->validate([
            'vault' => ['nullable', 'integer'],
            'vault_name' => ['nullable', Rule::requiredIf(! $accountImport->holdsSeveralVaults() && $request->string('vault')->isEmpty()), 'string', 'max:255'],
            'me' => ['nullable', 'string', 'max:64'],
        ]);

        $vault = null;

        if (($validated['vault'] ?? null) !== null) {
            $vault = Vault::query()
                ->where('account_id', $request->user()->account_id)
                ->find($validated['vault']);

            abort_unless($vault instanceof Vault, 404);
        }

        try {
            new StartAccountImport(
                user: $request->user(),
                import: $accountImport,
                vault: $vault,
                vaultName: $validated['vault_name'] ?? null,
                meSourceUuid: $validated['me'] ?? null,
            )->execute();
        } catch (ImportExceedsALimit|InvalidArgumentException|AuthorizationException $exception) {
            return redirect()->route('settings.accountImports.show', $accountImport)
                ->withInput()
                ->withErrors(['vault' => $exception->getMessage()]);
        }

        return redirect()->route('settings.accountImports.show', $accountImport)
            ->with('status', __('The import has started.'));
    }

    public function destroy(Request $request, AccountImport $accountImport): RedirectResponse
    {
        try {
            new CancelAccountImport(
                user: $request->user(),
                import: $accountImport,
            )->execute();
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('settings.accountImports.show', $accountImport)
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('settings.accountImports.index')
            ->with('status', __('The import is cancelled.'));
    }
}
