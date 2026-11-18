<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionLoan;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Enums\PossessionStatusEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionLoan;
use App\Models\PossessionProvenanceEvent;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionLoanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lends_a_possession_to_a_contact_of_its_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'World’s Best Boss mug']);
        $dwight = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $good = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

        $loan = new CreatePossessionLoan(
            user: $user,
            possession: $possession,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Active,
            startedOn: CarbonImmutable::parse('2024-06-12'),
            contact: $dwight,
            dueOn: CarbonImmutable::parse('2024-06-15'),
            conditionOut: $good,
            depositAmount: '50',
            depositCurrency: CurrencyEnum::UsDollar,
            purpose: ' <b>Beet harvest party</b> ',
        )->execute();

        $this->assertInstanceOf(PossessionLoan::class, $loan);
        $this->assertDatabaseHas('possession_loans', [
            'id' => $loan->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'possession_id' => $possession->id,
            'contact_id' => $dwight->id,
            'direction' => 'lent',
            'status' => 'active',
            'returned_on' => null,
            'condition_out_id' => $good->id,
            'deposit_amount' => '50.00',
            'deposit_currency' => 'usd',
            'purpose' => 'Beet harvest party',
            'is_in_provenance' => false,
            'departure_provenance_event_id' => null,
        ]);
        $this->assertEquals('2024-06-15', $loan->due_on?->toDateString());
        $this->assertDatabaseCount('possession_transactions', 0);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionLoanCreation
                && $job->parameters === ['name' => 'World’s Best Boss mug'],
        );
    }

    #[Test]
    public function it_creates_the_contact_with_the_loan(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $version->update(['status' => VersionStatusEnum::Published]);
        VaultDefaultTemplate::query()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact,
            'structure_version_id' => $version->id,
        ]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);

        $loan = new CreatePossessionLoan(
            user: $user,
            possession: $possession,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Active,
            startedOn: CarbonImmutable::parse('2024-06-12'),
            newContactFirstName: 'Nate',
            newContactLastName: 'Nickerson',
        )->execute();

        $this->assertEquals('Nate Nickerson', $loan->contact?->name);
        $this->assertEquals($vault->id, $loan->contact?->vault_id);
    }

    #[Test]
    public function a_second_active_loan_is_refused_before_any_contact_is_created(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionLoan::factory()->active()->create(['possession_id' => $possession->id]);
        $contactsBefore = Contact::query()->count();

        try {
            new CreatePossessionLoan(
                user: $user,
                possession: $possession,
                direction: PossessionLoanDirectionEnum::Lent,
                status: PossessionLoanStatusEnum::Active,
                startedOn: CarbonImmutable::parse('2024-06-12'),
                newContactFirstName: 'Nate',
            )->execute();
            $this->fail('A second active loan was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertEquals($contactsBefore, Contact::query()->count());
    }

    #[Test]
    public function a_sold_possession_takes_a_returned_loan_and_refuses_an_active_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'status' => PossessionStatusEnum::Sold]);
        $toby = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Toby']);

        $loan = new CreatePossessionLoan(
            user: $user,
            possession: $possession,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Returned,
            startedOn: CarbonImmutable::parse('2019-03-01'),
            contact: $toby,
            returnedOn: CarbonImmutable::parse('2019-04-01'),
        )->execute();

        $this->assertModelExists($loan);

        try {
            new CreatePossessionLoan(
                user: $user,
                possession: $possession,
                direction: PossessionLoanDirectionEnum::Lent,
                status: PossessionLoanStatusEnum::Active,
                startedOn: CarbonImmutable::parse('2024-06-12'),
                newContactFirstName: 'Kevin',
            )->execute();
            $this->fail('An active loan of a sold possession was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertDatabaseMissing('contacts', ['first_name' => 'Kevin']);
    }

    #[Test]
    public function it_refuses_a_loan_without_a_contact_or_a_first_name(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);

        foreach ([null => 'contact_id', '  ' => 'new_contact_first_name'] as $firstName => $field) {
            try {
                new CreatePossessionLoan(
                    user: $user,
                    possession: $possession,
                    direction: PossessionLoanDirectionEnum::Lent,
                    status: PossessionLoanStatusEnum::Active,
                    startedOn: CarbonImmutable::parse('2024-06-12'),
                    newContactFirstName: $firstName === '' ? null : (string) $firstName,
                )->execute();
                $this->fail('A loan without a contact was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($field, $exception->errors());
            }
        }
    }

    #[Test]
    public function it_refuses_dates_that_do_not_follow_the_start(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $cases = [
            'due_on' => [PossessionLoanStatusEnum::Active, '2024-06-01', null],
            'returned_on' => [PossessionLoanStatusEnum::Returned, null, '2024-06-01'],
        ];

        foreach ($cases as $field => [$status, $due, $returned]) {
            try {
                new CreatePossessionLoan(
                    user: $user,
                    possession: $possession,
                    direction: PossessionLoanDirectionEnum::Lent,
                    status: $status,
                    startedOn: CarbonImmutable::parse('2024-06-12'),
                    contact: $contact,
                    dueOn: $due === null ? null : CarbonImmutable::parse($due),
                    returnedOn: $returned === null ? null : CarbonImmutable::parse($returned),
                )->execute();
                $this->fail("A {$field} before the start was accepted.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($field, $exception->errors());
            }
        }
    }

    #[Test]
    public function it_refuses_a_returned_loan_without_its_return_day(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(ValidationException::class);

        new CreatePossessionLoan(
            user: $user,
            possession: $possession,
            direction: PossessionLoanDirectionEnum::Borrowed,
            status: PossessionLoanStatusEnum::Returned,
            startedOn: CarbonImmutable::parse('2024-06-12'),
            contact: $contact,
        )->execute();
    }

    #[Test]
    public function it_writes_two_change_of_custody_events_when_the_loan_is_in_the_provenance(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $jan = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);

        $loan = new CreatePossessionLoan(
            user: $user,
            possession: $possession,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Returned,
            startedOn: CarbonImmutable::parse('2024-06-12'),
            contact: $jan,
            returnedOn: CarbonImmutable::parse('2024-06-20'),
            isInProvenance: true,
        )->execute();

        $departure = PossessionProvenanceEvent::query()->findOrFail($loan->departure_provenance_event_id);
        $return = PossessionProvenanceEvent::query()->findOrFail($loan->return_provenance_event_id);

        $this->assertEquals(PossessionProvenanceEventTypeEnum::Custody, $departure->type);
        $this->assertEquals('Lent to Jan Levinson', $departure->title);
        $this->assertEquals('Jan Levinson', $departure->to);
        $this->assertEquals('2024-06-12', $departure->occurred_on?->toDateString());
        $this->assertEquals('Returned by Jan Levinson', $return->title);
        $this->assertEquals('Jan Levinson', $return->from);
        $this->assertEquals('2024-06-20', $return->occurred_on?->toDateString());
    }

    #[Test]
    public function it_writes_no_event_for_a_planned_loan(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $loan = new CreatePossessionLoan(
            user: $user,
            possession: $possession,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Planned,
            startedOn: CarbonImmutable::parse('2024-06-12'),
            contact: $contact,
            isInProvenance: true,
        )->execute();

        $this->assertNull($loan->departure_provenance_event_id);
        $this->assertDatabaseCount('possession_provenance_events', 0);
    }

    #[Test]
    public function it_refuses_a_contact_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create(['vault_id' => $otherVault->id]);

        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionLoan(
            user: $user,
            possession: $possession,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Active,
            startedOn: CarbonImmutable::parse('2024-06-12'),
            contact: $contact,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $possession->vault_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionLoan(
            user: $user,
            possession: $possession,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Active,
            startedOn: CarbonImmutable::parse('2024-06-12'),
            contact: $contact,
        )->execute();
    }
}
