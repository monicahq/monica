<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionTag as UpdatePossessionTagAction;
use App\Enums\VaultColourEnum;
use App\Http\Resources\PossessionTagResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a possession tag or give it another vault colour. What you leave out keeps its value. The change is written to the account journal.')]
class UpdatePossessionTag extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTags.update';

    public function handle(Request $request): Response
    {
        $address = $request->validate([
            'possession_tag' => ['required', 'string'],
        ]);

        $possessionTag = $this->possessionTag($request, $address['possession_tag']);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'colour' => ['sometimes', 'required', Rule::enum(VaultColourEnum::class)],
        ]);

        new UpdatePossessionTagAction(
            user: $this->user($request),
            possessionTag: $possessionTag,
            name: $validated['name'] ?? $possessionTag->name,
            colour: VaultColourEnum::from($validated['colour'] ?? $possessionTag->colour),
        )->execute();

        return Response::json(new PossessionTagResource($possessionTag)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_tag' => $schema->string()->description('The identifier of the possession tag, as list-possession-tags gives it.')->required(),
            'name' => $schema->string()->description('The new name, up to 50 characters.'),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('One of the vault colours, as a hexadecimal value.'),
        ];
    }
}
