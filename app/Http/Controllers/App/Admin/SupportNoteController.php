<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\AddSupportNote;
use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportNoteController extends Controller
{
    public function create(Request $request, string $supportRequest): RedirectResponse
    {
        $supportRequest = SupportRequest::query()->where('uuid', $supportRequest)->firstOrFail();

        $validated = $request->validateWithBag('note', [
            'body' => ['required', 'string', 'max:20000'],
        ]);

        new AddSupportNote(
            administrator: $request->user(),
            supportRequest: $supportRequest,
            body: $validated['body'],
        )->execute();

        return redirect()->route('admin.support.show', ['supportRequest' => $supportRequest->uuid])
            ->with('status', __('Note added.'));
    }
}
