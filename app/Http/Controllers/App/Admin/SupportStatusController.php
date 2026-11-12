<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\ChangeSupportRequestStatus;
use App\Enums\SupportRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportStatusController extends Controller
{
    public function update(Request $request, string $supportRequest): RedirectResponse
    {
        $supportRequest = SupportRequest::query()->where('uuid', $supportRequest)->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', Rule::enum(SupportRequestStatusEnum::class)],
        ]);

        new ChangeSupportRequestStatus(
            administrator: $request->user(),
            supportRequest: $supportRequest,
            status: SupportRequestStatusEnum::from($validated['status']),
        )->execute();

        return redirect()->route('admin.support.show', ['supportRequest' => $supportRequest->uuid])
            ->with('status', __('Status changed.'));
    }
}
