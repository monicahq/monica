<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Support;

use App\Actions\OpenSupportRequest;
use App\Enums\SupportCategoryEnum;
use App\Helpers\SupportAvailability;
use App\Http\Controllers\Controller;
use App\Models\SupportAttachment;
use App\Models\SupportRequest;
use App\ViewModels\Support\SupportRequestFormViewModel;
use App\ViewModels\Support\SupportRequestsViewModel;
use App\ViewModels\Support\SupportRequestViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportRequestController extends Controller
{
    public function index(Request $request, ?string $view = null): View
    {
        return view('app.support.index', [
            'viewModel' => new SupportRequestsViewModel(
                user: $request->user(),
                showSolved: $view === 'solved',
            ),
        ]);
    }

    public function new(Request $request): View
    {
        abort_unless(SupportAvailability::isOffered(), 404);

        return view('app.support.new', [
            'viewModel' => new SupportRequestFormViewModel(
                user: $request->user(),
                selectedCategory: $request->old('category'),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        abort_unless(SupportAvailability::isOffered(), 404);

        $validated = $request->validate([
            'category' => ['required', Rule::enum(SupportCategoryEnum::class)],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10', 'max:20000'],
            'files' => ['nullable', 'array', 'max:'.SupportAttachment::MAXIMUM_PER_MESSAGE],
            'files.*' => ['file', 'max:'.SupportAttachment::MAXIMUM_SIZE_IN_KILOBYTES, 'mimes:'.SupportAttachment::ACCEPTED_TYPES],
        ]);

        $supportRequest = new OpenSupportRequest(
            user: $request->user(),
            category: SupportCategoryEnum::from($validated['category']),
            subject: $validated['subject'],
            body: $validated['body'],
            files: array_values($request->file('files', [])),
        )->execute();

        return redirect()->route('support.requests.confirmation.show', $supportRequest);
    }

    public function show(Request $request, SupportRequest $supportRequest): View
    {
        return view('app.support.show', [
            'viewModel' => new SupportRequestViewModel(
                user: $request->user(),
                supportRequest: $supportRequest,
            ),
        ]);
    }
}
