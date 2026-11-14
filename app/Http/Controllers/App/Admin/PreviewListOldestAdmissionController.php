<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\AdmitOldestPreviewListEntries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PreviewListOldestAdmissionController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $admitted = new AdmitOldestPreviewListEntries(
            administrator: $request->user(),
            count: (int) $validated['count'],
        )->execute();

        $status = $admitted === 0
            ? __('Nobody was admitted.')
            : trans_choice(':count person was admitted.|:count people were admitted.', $admitted);

        return redirect()->route('admin.previewList.index')->with('status', $status);
    }
}
