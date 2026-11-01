<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateGroup as UpdateGroupAction;
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

#[Description('Change the name, the description or the dates of a group. What is left out keeps its value. The change is written to the account journal.')]
class UpdateGroup extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.update';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && FlagState::isOn('groups', $user->account);
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'group' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'started_on' => ['nullable', 'date_format:Y-m-d'],
            'started_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
            'ended_on' => ['nullable', 'date_format:Y-m-d'],
            'ended_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $group = $this->group($vault, $validated['group']);

        $startedOn = array_key_exists('started_on', $validated) ? $validated['started_on'] : $group->started_on?->format('Y-m-d');
        $endedOn = array_key_exists('ended_on', $validated) ? $validated['ended_on'] : $group->ended_on?->format('Y-m-d');
        $startedPrecision = $validated['started_precision'] ?? $group->started_precision->value;
        $endedPrecision = $validated['ended_precision'] ?? $group->ended_precision->value;

        new UpdateGroupAction(
            user: $this->user($request),
            vault: $vault,
            group: $group,
            name: $validated['name'] ?? $group->name,
            description: array_key_exists('description', $validated) ? $validated['description'] : $group->description,
            startedOn: $startedOn !== null ? CarbonImmutable::parse($startedOn) : null,
            startedPrecision: $startedPrecision === DatePrecisionEnum::Unknown->value ? DatePrecisionEnum::Day : DatePrecisionEnum::from($startedPrecision),
            endedOn: $endedOn !== null ? CarbonImmutable::parse($endedOn) : null,
            endedPrecision: $endedPrecision === DatePrecisionEnum::Unknown->value ? DatePrecisionEnum::Day : DatePrecisionEnum::from($endedPrecision),
        )->execute();

        return Response::json(new GroupResource($this->group($vault, $group->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'name' => $schema->string()->description('A new name.'),
            'description' => $schema->string()->description('A new description. Null clears it.'),
            'started_on' => $schema->string()->description('When the group came together, as YYYY-MM-DD. Null clears it.'),
            'started_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the start is known.'),
            'ended_on' => $schema->string()->description('When the group ended, as YYYY-MM-DD. Null clears it.'),
            'ended_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the end is known.'),
        ];
    }
}
