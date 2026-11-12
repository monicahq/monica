<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossession as UpdatePossessionAction;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionStatusEnum;
use App\Http\Resources\PossessionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change what is known of a possession: its name, description, location, identifier, status, quantity, whether it is featured, what it would cost to replace, or its tags. How it was acquired is a transaction, changed with update-possession-transaction. Its type never changes. What you leave out keeps its value; send null to clear it. The change is written to the account journal.')]
class UpdatePossession extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.update';

    public function handle(Request $request): Response
    {
        $address = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $address['vault']);
        $possession = $this->possession($vault, $address['possession']);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'location_id' => ['sometimes', 'nullable', 'string'],
            'identifier' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::enum(PossessionStatusEnum::class)],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000000'],
            'is_featured' => ['sometimes', 'required', 'boolean'],
            'declared_value' => ['sometimes', 'nullable', 'numeric'],
            'declared_value_currency' => ['sometimes', 'nullable', Rule::enum(CurrencyEnum::class)],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $location = $possession->location;

        if (array_key_exists('location_id', $validated)) {
            $location = $validated['location_id'] === null ? null : $this->location($request, $validated['location_id']);
        }

        $declaredValue = array_key_exists('declared_value', $validated) ? ($validated['declared_value'] === null ? null : (string) $validated['declared_value']) : $possession->declared_value;
        $declaredValueCurrency = array_key_exists('declared_value_currency', $validated) ? CurrencyEnum::tryFrom($validated['declared_value_currency'] ?? '') : CurrencyEnum::tryFrom($possession->declared_value_currency ?? '');

        new UpdatePossessionAction(
            user: $this->user($request),
            possession: $possession,
            name: $validated['name'] ?? $possession->name,
            description: array_key_exists('description', $validated) ? $validated['description'] : $possession->description,
            location: $location,
            identifier: array_key_exists('identifier', $validated) ? $validated['identifier'] : $possession->identifier,
            status: isset($validated['status']) ? PossessionStatusEnum::from($validated['status']) : $possession->status,
            quantity: (int) ($validated['quantity'] ?? $possession->quantity),
            isFeatured: (bool) ($validated['is_featured'] ?? $possession->is_featured),
            declaredValue: $declaredValue,
            declaredValueCurrency: $declaredValueCurrency,
            tagNames: $validated['tags'] ?? null,
        )->execute();

        return Response::json(new PossessionResource($possession->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'name' => $schema->string()->description('The new name.'),
            'description' => $schema->string()->nullable()->description('A free description, up to 2,000 characters, or null for none.'),
            'location_id' => $schema->string()->nullable()->description('The identifier of the place where it is now, as list-locations gives it, or null when unknown.'),
            'identifier' => $schema->string()->nullable()->description('A serial number or another identifier, or null for none.'),
            'status' => $schema->string()->enum(array_column(PossessionStatusEnum::cases(), 'value'))->description('The new status.'),
            'quantity' => $schema->integer()->description('How many, from 1 to 1,000,000.'),
            'is_featured' => $schema->boolean()->description('Whether it is shown among the main possessions. Becoming featured places it last; stopping takes it out of the order.'),
            'declared_value' => $schema->number()->nullable()->description('What it would cost to replace today, 0 or more, with two decimals.'),
            'declared_value_currency' => $schema->string()->nullable()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the declared value. Required with a value.'),
            'tags' => $schema->array()->items($schema->string())->description('The names of all its tags, replacing the ones it carries. A name matching a tag of the account, ignoring case, reuses it; any other name creates a tag.'),
        ];
    }
}
