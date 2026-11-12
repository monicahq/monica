<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\OpenSupportRequest;
use App\Enums\SupportCategoryEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Helpers\SupportAvailability;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupportRequestResource;
use App\Models\SupportAttachment;
use App\Models\SupportRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(SupportRequestStatusEnum::class)],
        ]);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $supportRequests = SupportRequest::query()
            ->where('account_id', $request->user()->account_id)
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('status', $validated['status']))
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => array_map(
                fn (SupportRequest $supportRequest): array => new SupportRequestResource($supportRequest)->resolve($request),
                $supportRequests->items(),
            ),
            'meta' => [
                'page' => $supportRequests->currentPage(),
                'per_page' => $supportRequests->perPage(),
                'total' => $supportRequests->total(),
            ],
        ]);
    }

    public function show(Request $request, string $supportRequest): JsonResponse
    {
        $supportRequest = $this->supportRequest($request, $supportRequest);

        return new SupportRequestResource($supportRequest->load('visibleMessages.attachments'))->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
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

        return new SupportRequestResource($supportRequest->load('visibleMessages.attachments'))->response()->setStatusCode(201);
    }

    private function supportRequest(Request $request, string $address): SupportRequest
    {
        return SupportRequest::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $address)
            ->firstOrFail();
    }
}
