<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\UpdateAccountStorageLimit;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountStorageLimitController extends Controller
{
    public function update(Request $request, Account $account): RedirectResponse
    {
        $validated = $request->validate([
            'storage_limit_in_bytes' => ['nullable', 'integer', 'min:0'],
        ]);

        // A form sends every field as text, and the integer rule checks the value
        // without changing its type, so what arrives here is the string "1048576"
        // rather than the number. An empty field is already null by the time it
        // reaches this, which is what tells no limit apart from a limit of zero.
        $limit = $validated['storage_limit_in_bytes'] ?? null;

        new UpdateAccountStorageLimit(
            account: $account,
            administrator: $request->user(),
            limitInBytes: $limit === null ? null : (int) $limit,
        )->execute();

        return redirect()->route('admin.accounts.show', ['account' => $account->id])
            ->with('status', __('The storage limit is saved.'))
            ->with('status_description', __('No file has been deleted. A limit under what the account holds only stops the next upload.'));
    }
}
