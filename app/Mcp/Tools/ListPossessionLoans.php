<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\PossessionLoanResource;
use App\Models\PossessionLoan;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the loans of a possession, newest first: each time it was lent to or borrowed from a contact of its vault, when, whether it is planned, active or returned, and whether it is overdue. The active loan says who has it now. A loan never changes the owner.')]
#[IsReadOnly]
class ListPossessionLoans extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.loans';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);
        $possession->load('loans.contact', 'loans.conditionOut', 'loans.conditionBack');

        $loans = $possession->loans
            ->reverse()
            ->values()
            ->each(fn (PossessionLoan $loan) => $loan->setRelation('possession', $possession));

        return Response::json(['data' => PossessionLoanResource::collection($loans)->resolve()]);
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
