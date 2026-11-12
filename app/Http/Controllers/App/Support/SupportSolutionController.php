<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Support;

use App\Actions\SolveSupportRequest;
use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportSolutionController extends Controller
{
    public function create(Request $request, SupportRequest $supportRequest): RedirectResponse
    {
        try {
            new SolveSupportRequest(
                user: $request->user(),
                supportRequest: $supportRequest,
            )->execute();
        } catch (AuthorizationException $e) {
            return redirect()->route('support.requests.show', $supportRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('support.requests.show', $supportRequest)
            ->with('status', __('Marked as solved.'))
            ->with('status_description', __('If it comes back, write in it again and it reopens.'));
    }
}
