<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\SendSupportReply;
use App\Enums\SupportRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportReplyController extends Controller
{
    public function create(Request $request, string $supportRequest): RedirectResponse
    {
        $supportRequest = SupportRequest::query()->where('uuid', $supportRequest)->firstOrFail();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::enum(SupportRequestStatusEnum::class)],
        ]);

        new SendSupportReply(
            administrator: $request->user(),
            supportRequest: $supportRequest,
            body: $validated['body'],
            statusAfter: SupportRequestStatusEnum::from($validated['status']),
        )->execute();

        return redirect()->route('admin.support.show', ['supportRequest' => $supportRequest->uuid])
            ->with('status', __('Your reply is sent.'));
    }
}
