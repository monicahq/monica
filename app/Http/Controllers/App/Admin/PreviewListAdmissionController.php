<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\AdmitPreviewListEntry;
use App\Exceptions\AddressAlreadyHoldsAnAccount;
use App\Http\Controllers\Controller;
use App\Models\PreviewListEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PreviewListAdmissionController extends Controller
{
    public function create(Request $request, PreviewListEntry $previewListEntry): RedirectResponse
    {
        try {
            new AdmitPreviewListEntry(
                administrator: $request->user(),
                entry: $previewListEntry,
            )->execute();
        } catch (AddressAlreadyHoldsAnAccount $exception) {
            return back()->withErrors(['previewList' => $exception->getMessage()]);
        }

        return redirect()->route('admin.previewList.index')
            ->with('status', __(':email is admitted.', ['email' => $previewListEntry->email]))
            ->with('status_description', __('They have been sent a link to create their account.'));
    }
}
