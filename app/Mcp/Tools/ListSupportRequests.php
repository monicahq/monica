<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\SupportRequestStatusEnum;
use App\Helpers\SupportAvailability;
use App\Http\Resources\SupportRequestResource;
use App\Models\SupportRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the support requests the account has opened, the latest activity first, a page at a time: number, subject, category and status. A status of waiting_on_user means the support team has replied.')]
#[IsReadOnly]
class ListSupportRequests extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.supportRequests';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() !== null && SupportAvailability::isEnabled();
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(SupportRequestStatusEnum::class)],
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $perPage = max(1, min(
            (int) ($validated['per_page'] ?? config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $supportRequests = SupportRequest::query()
            ->where('account_id', $this->user($request)->account_id)
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('status', $validated['status']))
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => array_map(fn (SupportRequest $supportRequest): array => new SupportRequestResource($supportRequest)->resolve(), $supportRequests->items()),
            'meta' => [
                'page' => $supportRequests->currentPage(),
                'per_page' => $supportRequests->perPage(),
                'total' => $supportRequests->total(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(SupportRequestStatusEnum::values())->description('Only the requests in this status.'),
            'page' => $schema->integer()->description('Which page to return, starting at 1.'),
            'per_page' => $schema->integer()->description('How many requests per page.'),
        ];
    }
}
