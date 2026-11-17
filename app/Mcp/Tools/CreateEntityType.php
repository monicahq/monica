<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\EntityType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a kind of record the account keeps besides contacts, such as Animal or Wine bottle. The change is written to the account log.')]
class CreateEntityType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.create';

    public function handle(Request $request): Response
    {
        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name_singular' => $schema->string()->description('The name of one record, such as Wine bottle.')->required(),
            'name_plural' => $schema->string()->description('The name of several records, such as Wine bottles.')->required(),
            'description' => $schema->string()->description('A sentence saying what these records are for.'),
            'icon' => $schema->string()->enum(EntityType::ICONS)->description('The icon shown next to these records.'),
            'colour' => $schema->string()->enum(EntityType::COLOURS)->description('The colour of these records.'),
            'is_active' => $schema->boolean()->description('Whether new records can be added. True when left out.'),
            'slug' => $schema->string()->description('The part of the URL naming these records, in lowercase letters, numbers and dashes. Derived from the singular name when left out, and fixed once created.'),
        ];
    }
}
