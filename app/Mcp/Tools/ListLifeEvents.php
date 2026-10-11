<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\LifeEventResource;
use App\Models\Contact;
use App\Models\LifeEvent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the life events of one vault, the most recent first, a page at a time, optionally only those one contact took part in. Values of sensitive fields are never given.')]
#[IsReadOnly]
class ListLifeEvents extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEvents';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['nullable', 'string'],
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = isset($validated['contact']) ? $this->contact($vault, $validated['contact']) : null;

        $perPage = max(1, min(
            (int) ($validated['per_page'] ?? config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $lifeEvents = LifeEvent::query()
            ->where('vault_id', $vault->id)
            ->when($contact, fn (Builder $query, Contact $contact): Builder => $query->whereHas('participants', fn (Builder $query): Builder => $query
                ->where('participant_type', $contact->getMorphClass())
                ->where('participant_id', $contact->id)))
            ->with(LifeEventResource::RELATIONS)
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => LifeEventResource::collection($lifeEvents->items())->resolve(),
            'meta' => [
                'page' => $lifeEvents->currentPage(),
                'per_page' => $lifeEvents->perPage(),
                'total' => $lifeEvents->total(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'vault' => $schema->string()->description('The address of the vault, as list-vaults gives it.')->required(),
            'contact' => $schema->string()->description('Only the life events this contact took part in, by the address list-contacts gives.'),
            'page' => $schema->integer()->description('Which page to return, counting from 1.'),
            'per_page' => $schema->integer()->description("How many life events a page holds, {$default} by default and {$maximum} at most."),
        ];
    }
}
