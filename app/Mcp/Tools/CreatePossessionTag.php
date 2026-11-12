<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionTag as CreatePossessionTagAction;
use App\Enums\VaultColourEnum;
use App\Http\Resources\PossessionTagResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a possession tag to the account: a word to put on possessions, such as “insured” or “to sell”. Its name is unique in the account ignoring case. The change is written to the account journal.')]
class CreatePossessionTag extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTags.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'colour' => ['nullable', Rule::enum(VaultColourEnum::class)],
        ]);

        $possessionTag = new CreatePossessionTagAction(
            user: $this->user($request),
            name: $validated['name'],
            colour: isset($validated['colour']) ? VaultColourEnum::from($validated['colour']) : VaultColourEnum::Blue,
        )->execute();

        return Response::json(new PossessionTagResource($possessionTag)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('What the tag reads, up to 50 characters.')->required(),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('One of the vault colours, as a hexadecimal value. Blue when left out.'),
        ];
    }
}
