<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\SupportQueue;
use App\Http\Resources\Admin\SupportRequestResource;
use App\Models\SupportRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('For an administrator of the instance: list the support requests of every account, oldest first, in one view (to_handle by default, waiting_on_user, solved or all), with the count of each view. Filter by text, category, user id or age (day, week, old).')]
#[IsReadOnly]
class ListSupportQueue extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.admin.supportRequests';

    public function shouldRegister(Request $request): bool
    {
        return $this->administers($request);
    }

    public function handle(Request $request): Response
    {
        if (! $this->administers($request)) {
            throw $this->doesNotExist(__('That support request does not exist.'));
        }

        $validated = $request->validate([
            'view' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string'],
            'user' => ['nullable', 'integer'],
            'age' => ['nullable', 'string'],
            'page' => ['nullable', 'integer'],
        ]);

        $queue = new SupportQueue(
            view: (string) ($validated['view'] ?? ''),
            search: (string) ($validated['q'] ?? ''),
            category: (string) ($validated['category'] ?? ''),
            user: (string) ($validated['user'] ?? ''),
            age: (string) ($validated['age'] ?? ''),
        );

        $supportRequests = $queue->query()
            ->with('user')
            ->paginate((int) config('monica.api_maximum_items_per_page'), ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => array_map(fn (SupportRequest $supportRequest): array => new SupportRequestResource($supportRequest)->resolve(), $supportRequests->items()),
            'meta' => [
                'page' => $supportRequests->currentPage(),
                'total' => $supportRequests->total(),
                'counts' => $queue->counts(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'view' => $schema->string()->enum(['to_handle', 'waiting_on_user', 'solved', 'all'])->description('Which requests to list.'),
            'q' => $schema->string()->description('Words found in the subject, a message, the name or the email of the user, or the request number.'),
            'category' => $schema->string()->enum(['feature', 'billing', 'email', 'question', 'technical'])->description('Only this category.'),
            'user' => $schema->integer()->description('Only the requests of this user id.'),
            'age' => $schema->string()->enum(['day', 'week', 'old'])->description('Opened in the last day, between one and seven days ago, or longer ago.'),
            'page' => $schema->integer()->description('Which page to return, starting at 1.'),
        ];
    }
}
