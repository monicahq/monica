<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionStatusEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Record an object somebody owns in a vault. It has one possession type, and optionally a location, what it would cost to replace and tags. Unless given, its status is owned, its quantity is 1 and it is not featured. Record how it was acquired with create-possession-transaction. Write the values of its fields with set-possession-values. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreatePossession extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        return $this->answerThroughApi($request, $vault->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'type_id' => $schema->string()->description('The id of its possession type, as list-possession-types gives it. The type never changes afterwards.')->required(),
            'name' => $schema->string()->description('What it is called, like “Michael’s Chrysler Sebring”.')->required(),
            'description' => $schema->string()->description('A free description, up to 2,000 characters.'),
            'location_id' => $schema->string()->description('The id of the location where it is now, as list-locations gives it.'),
            'identifier' => $schema->string()->description('A serial number, a vehicle identification number or a frame number.'),
            'status' => $schema->string()->enum(array_column(PossessionStatusEnum::cases(), 'value'))->description('Its status. Owned when not given.'),
            'quantity' => $schema->integer()->description('How many, from 1 to 1,000,000. 1 when not given.'),
            'is_featured' => $schema->boolean()->description('Whether it is shown among the main possessions of the vault. A featured possession is placed last among them.'),
            'declared_value' => $schema->number()->description('What it would cost to replace today, 0 or more, with two decimals.'),
            'declared_value_currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the declared value. Required when declared_value is given.'),
            'tags' => $schema->array()->items($schema->string())->description('The names of its tags. A name matching a tag of the account, ignoring case, reuses that tag. Any other name creates a tag.'),
        ];
    }
}
