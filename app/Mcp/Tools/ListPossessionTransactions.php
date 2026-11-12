<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\PossessionTransactionResource;
use App\Models\PossessionTransaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the transactions of a possession, oldest first, undated ones before the others: money and ownership changing hands, with their figures, their total and which one is the acquisition.')]
#[IsReadOnly]
class ListPossessionTransactions extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.transactions';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);
        $possession->load('transactions.documents');

        $transactions = $possession->transactionsOldestFirst()
            ->each(fn (PossessionTransaction $transaction) => $transaction->setRelation('possession', $possession));

        return Response::json(['data' => PossessionTransactionResource::collection($transactions)->resolve()]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
        ];
    }
}
