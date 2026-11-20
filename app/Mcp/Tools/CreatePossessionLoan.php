<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Record a possession lent to a contact of its vault, or borrowed from one, without any change of owner. Give an existing contact in contact_id, or a new contact to write down with the loan in new_contact. Only one loan of a possession can be active at a time. A loan in the provenance also records a change of custody when it starts and when it ends. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreatePossessionLoan extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.loans.create';

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
            'direction' => $schema->string()->enum(array_column(PossessionLoanDirectionEnum::cases(), 'value'))->description('lent when it leaves your hands, borrowed when it comes into them.')->required(),
            'status' => $schema->string()->enum(array_column(PossessionLoanStatusEnum::cases(), 'value'))->description('planned, active or returned.')->required(),
            'contact_id' => $schema->string()->description('The id of a contact of the vault, as list-contacts gives it.'),
            'new_contact' => $schema->object([
                'first_name' => $schema->string()->description('The first name of the new contact.')->required(),
                'last_name' => $schema->string()->description('The last name of the new contact.'),
            ])->description('A new contact to write down with the loan, when no contact_id is given.'),
            'started_on' => $schema->string()->description('The day the loan started, as YYYY-MM-DD.')->required(),
            'due_on' => $schema->string()->description('The day it is due back, as YYYY-MM-DD.'),
            'returned_on' => $schema->string()->description('The day it came back, as YYYY-MM-DD. Required for a returned loan.'),
            'condition_out_id' => $schema->string()->description('The id of the condition it left in, as list-possession-conditions gives it.'),
            'condition_back_id' => $schema->string()->description('The id of the condition it came back in, as list-possession-conditions gives it.'),
            'deposit_amount' => $schema->number()->description('A deposit left against the loan, 0 or more.'),
            'deposit_currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the deposit.'),
            'purpose' => $schema->string()->description('Why it was lent or borrowed.'),
            'is_in_provenance' => $schema->boolean()->description('Whether the loan records changes of custody in the provenance.'),
        ];
    }
}
