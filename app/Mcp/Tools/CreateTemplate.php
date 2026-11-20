<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\StructureAppliesToEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Create a template for contacts, for a kind of entity or for life events. It starts with a first draft. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateTemplate extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.create';

    public function handle(Request $request): Response
    {
        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name of the template.')->required(),
            'applies_to' => $schema->string()->enum(array_column(StructureAppliesToEnum::cases(), 'value'))->description('What the template describes.')->required(),
            'description' => $schema->string()->nullable()->description('A sentence saying what the template is for.'),
            'entity_type_id' => $schema->string()->nullable()->description('The id of the kind of entity the template describes, as list-entity-types gives it. Required when applies_to is entity.'),
        ];
    }
}
