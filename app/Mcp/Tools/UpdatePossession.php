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

#[Description('Change what is known of a possession: its name, description, location, identifier, status, quantity, whether it is featured, what it would cost to replace, or its tags. How it was acquired is a transaction, changed with update-possession-transaction. Its type never changes. The change is written to the account log.')]
class UpdatePossession extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $possession = $this->possession($vault, $validated['possession']);

        return $this->answerThroughApi($request, $vault->uuid, $possession->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'possession' => $schema->string()->description('The id of the possession, as list-possessions gives it.')->required(),
            'name' => $schema->string()->description('The new name.'),
            'description' => $schema->string()->nullable()->description('A free description, up to 2,000 characters.'),
            'location_id' => $schema->string()->nullable()->description('The id of the location where it is now, as list-locations gives it.'),
            'identifier' => $schema->string()->nullable()->description('A serial number or another identifier.'),
            'status' => $schema->string()->enum(array_column(PossessionStatusEnum::cases(), 'value'))->description('The new status.'),
            'quantity' => $schema->integer()->description('How many, from 1 to 1,000,000.'),
            'is_featured' => $schema->boolean()->description('Whether it is shown among the main possessions. A possession that becomes featured is placed last. A possession that stops being featured leaves the order.'),
            'declared_value' => $schema->number()->nullable()->description('What it would cost to replace today, 0 or more, with two decimals.'),
            'declared_value_currency' => $schema->string()->nullable()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the declared value. Required when declared_value is given.'),
            'tags' => $schema->array()->items($schema->string())->nullable()->description('The names of all its tags, replacing the tags it carries. A name matching a tag of the account, ignoring case, reuses that tag. Any other name creates a tag.'),
        ];
    }
}
