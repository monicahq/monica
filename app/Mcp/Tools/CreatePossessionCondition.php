<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\VaultColourEnum;
use App\Models\Section;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Add a possession condition to the account: a state its possessions can be in, such as “mint” or “needs repair”. It goes last in the order, and its name is unique in the account ignoring case. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreatePossessionCondition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionConditions.create';

    public function handle(Request $request): Response
    {
        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('What the condition reads, up to 50 characters.')->required(),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('One of the vault colours, as a hexadecimal value. Blue when left out.'),
            'icon' => $schema->string()->enum(Section::ICONS)->description('One of the glyphs of the catalogue. None when left out.'),
        ];
    }
}
