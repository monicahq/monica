<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change an insurance record of a possession. The other records are not touched. The change is written to the account log.')]
class UpdatePossessionInsuranceRecord extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.insuranceRecords.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'insurance_record' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $possession = $this->possession($vault, $validated['possession']);
        $record = $this->possessionInsuranceRecord($possession, $validated['insurance_record']);

        return $this->answerThroughApi($request, $vault->uuid, $possession->uuid, $record->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'possession' => $schema->string()->description('The id of the possession, as list-possessions gives it.')->required(),
            'insurance_record' => $schema->string()->description('The id of the insurance record, as list-possession-insurance-records gives it.')->required(),
            'insurer' => $schema->string()->description('Who insures the possession, as text.'),
            'status' => $schema->string()->enum(array_column(PossessionInsuranceStatusEnum::cases(), 'value'))->description('Where the policy stands.'),
            'policy_number' => $schema->string()->description('The number of the policy.'),
            'coverage' => $schema->string()->description('What the policy covers, such as theft, fire and accidental damage.'),
            'insured_value' => $schema->number()->description('The value the possession is insured for, 0 or more.'),
            'excess' => $schema->number()->description('What is paid before the insurer pays, 0 or more.'),
            'currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the insured value and the excess.'),
            'starts_on' => $schema->string()->description('The day the policy period starts, as YYYY-MM-DD.'),
            'ends_on' => $schema->string()->description('The day the policy period ends or renews, as YYYY-MM-DD, or null for a policy with no end date.'),
            'is_listed_individually' => $schema->boolean()->description('Whether the policy names this possession, rather than covering it under the general contents of the home.'),
            'contact_name' => $schema->string()->description('Who to call at the insurer.'),
            'contact_email' => $schema->string()->description('The email of the person to call at the insurer.'),
            'contact_phone' => $schema->string()->description('The phone number of the person to call at the insurer.'),
            'note' => $schema->string()->description('Conditions, exclusions, what the insurer asked for.'),
        ];
    }
}
