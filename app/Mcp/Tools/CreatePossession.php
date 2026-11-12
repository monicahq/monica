<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossession as CreatePossessionAction;
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

#[Description('Record something owned in a vault, of one possession type, optionally at one location, with what it would cost to replace and its tags. Record how it was acquired with create-possession-transaction. It is owned, counts one and is not featured unless said otherwise. Write the values of its fields with set-possession-values. The change is written to the account journal.')]
class CreatePossession extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'type_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location_id' => ['nullable', 'string'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(PossessionStatusEnum::class)],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'is_featured' => ['nullable', 'boolean'],
            'declared_value' => ['nullable', 'numeric'],
            'declared_value_currency' => ['nullable', 'required_with:declared_value', Rule::enum(CurrencyEnum::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        $possession = new CreatePossessionAction(
            user: $this->user($request),
            vault: $vault,
            possessionType: $this->possessionType($request, $validated['type_id']),
            name: $validated['name'],
            description: $validated['description'] ?? null,
            location: isset($validated['location_id']) ? $this->location($request, $validated['location_id']) : null,
            identifier: $validated['identifier'] ?? null,
            status: PossessionStatusEnum::from($validated['status'] ?? PossessionStatusEnum::Owned->value),
            quantity: (int) ($validated['quantity'] ?? 1),
            isFeatured: (bool) ($validated['is_featured'] ?? false),
            declaredValue: isset($validated['declared_value']) ? (string) $validated['declared_value'] : null,
            declaredValueCurrency: CurrencyEnum::tryFrom($validated['declared_value_currency'] ?? ''),
            tagNames: $validated['tags'] ?? [],
        )->execute();

        return Response::json(new PossessionResource($possession)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault to record it in.')->required(),
            'type_id' => $schema->string()->description('The identifier of its possession type, as list-possession-types gives it. It never changes afterwards.')->required(),
            'name' => $schema->string()->description('What it is called, like “Michael’s Chrysler Sebring”.')->required(),
            'description' => $schema->string()->description('A free description, up to 2,000 characters.'),
            'location_id' => $schema->string()->description('The identifier of the place where it is now, as list-locations gives it.'),
            'identifier' => $schema->string()->description('A serial number, a vehicle identification number, a frame number.'),
            'status' => $schema->string()->enum(array_column(PossessionStatusEnum::cases(), 'value'))->description('Owned when left out.'),
            'quantity' => $schema->integer()->description('How many, from 1 to 1,000,000. One when left out.'),
            'is_featured' => $schema->boolean()->description('Whether it is shown among the main possessions of the vault. A featured one is placed last among them.'),
            'declared_value' => $schema->number()->description('What it would cost to replace today, 0 or more, with two decimals.'),
            'declared_value_currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the declared value. Required with a value.'),
            'tags' => $schema->array()->items($schema->string())->description('The names of its tags. A name matching a tag of the account, ignoring case, reuses it; any other name creates a tag.'),
        ];
    }
}
