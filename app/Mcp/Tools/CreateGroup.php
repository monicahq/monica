<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateGroup as CreateGroupAction;
use App\Enums\DatePrecisionEnum;
use App\Helpers\FlagState;
use App\Http\Resources\GroupResource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a group of contacts in a vault, such as a household, a couple or a team. It starts with nobody in it. The change is written to the account journal.')]
class CreateGroup extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.create';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && FlagState::isOn('groups', $user->account);
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'started_on' => ['nullable', 'date_format:Y-m-d'],
            'started_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
            'ended_on' => ['nullable', 'date_format:Y-m-d'],
            'ended_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        $group = new CreateGroupAction(
            user: $this->user($request),
            vault: $vault,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            startedOn: isset($validated['started_on']) ? CarbonImmutable::parse($validated['started_on']) : null,
            startedPrecision: DatePrecisionEnum::from($validated['started_precision'] ?? DatePrecisionEnum::Day->value),
            endedOn: isset($validated['ended_on']) ? CarbonImmutable::parse($validated['ended_on']) : null,
            endedPrecision: DatePrecisionEnum::from($validated['ended_precision'] ?? DatePrecisionEnum::Day->value),
        )->execute();

        return Response::json(new GroupResource($this->group($vault, $group->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault the group goes in.')->required(),
            'name' => $schema->string()->description('What the group is called.')->required(),
            'description' => $schema->string()->description('Anything to say about the group.'),
            'started_on' => $schema->string()->description('When the group came together, as YYYY-MM-DD. Null clears it.'),
            'started_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the start is known.'),
            'ended_on' => $schema->string()->description('When the group ended, as YYYY-MM-DD. Null clears it.'),
            'ended_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the end is known.'),
        ];
    }
}
