<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Support;

use App\Actions\AddSupportMessage;
use App\Http\Controllers\Controller;
use App\Models\SupportAttachment;
use App\Models\SupportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportMessageController extends Controller
{
    public function create(Request $request, SupportRequest $supportRequest): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'files' => ['nullable', 'array', 'max:'.SupportAttachment::MAXIMUM_PER_MESSAGE],
            'files.*' => ['file', 'max:'.SupportAttachment::MAXIMUM_SIZE_IN_KILOBYTES, 'mimes:'.SupportAttachment::ACCEPTED_TYPES],
        ]);

        new AddSupportMessage(
            user: $request->user(),
            supportRequest: $supportRequest,
            body: $validated['body'],
            files: array_values($request->file('files', [])),
        )->execute();

        return redirect()->route('support.requests.show', $supportRequest)
            ->with('status', __('Your reply is sent.'));
    }
}
