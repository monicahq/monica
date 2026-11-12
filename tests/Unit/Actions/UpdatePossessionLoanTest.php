<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionLoan;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionLoan;
use App\Models\PossessionProvenanceEvent;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionLoanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_a_loan_as_returned(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Office camera']);
        $loan = PossessionLoan::factory()->active()->create(['possession_id' => $possession->id, 'started_on' => '2024-06-12']);
        $worn = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

        $updated = new UpdatePossessionLoan(
            user: $user,
            loan: $loan,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Returned,
            startedOn: CarbonImmutable::parse('2024-06-12'),
            returnedOn: CarbonImmutable::parse('2024-06-15'),
            conditionBack: $worn,
        )->execute();

        $this->assertInstanceOf(PossessionLoan::class, $updated);
        $this->assertDatabaseHas('possession_loans', [
            'id' => $loan->id,
            'status' => 'returned',
            'condition_back_id' => $worn->id,
            'contact_id' => $loan->contact_id,
        ]);
        $this->assertEquals('2024-06-15', $updated->returned_on?->toDateString());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionLoanReturn
                && $job->parameters === ['name' => 'Office camera'],
        );
    }

    #[Test]
    public function a_loan_no_longer_returned_loses_its_return_day_and_condition_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $worn = PossessionCondition::factory()->create(['account_id' => $user->account_id]);
        $loan = PossessionLoan::factory()->returned()->create(['possession_id' => $possession->id, 'condition_back_id' => $worn->id]);
        $andy = Contact::factory()->create(['vault_id' => $vault->id]);

        new UpdatePossessionLoan(
            user: $user,
            loan: $loan,
            direction: PossessionLoanDirectionEnum::Borrowed,
            status: PossessionLoanStatusEnum::Active,
            startedOn: CarbonImmutable::parse('2024-06-10'),
            contact: $andy,
            returnedOn: CarbonImmutable::parse('2024-06-15'),
            conditionBack: $worn,
        )->execute();

        $this->assertDatabaseHas('possession_loans', [
            'id' => $loan->id,
            'direction' => 'borrowed',
            'status' => 'active',
            'returned_on' => null,
            'condition_back_id' => null,
            'contact_id' => $andy->id,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionLoanUpdate,
        );
    }

    #[Test]
    public function it_refuses_to_make_a_second_loan_active(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionLoan::factory()->active()->create(['possession_id' => $possession->id]);
        $planned = PossessionLoan::factory()->planned()->create(['possession_id' => $possession->id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionLoan(
            user: $user,
            loan: $planned,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Active,
            startedOn: CarbonImmutable::parse('2024-06-12'),
        )->execute();
    }

    #[Test]
    public function an_active_loan_may_be_saved_again_as_active(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->active()->create(['possession_id' => $possession->id]);

        new UpdatePossessionLoan(
            user: $user,
            loan: $loan,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Active,
            startedOn: CarbonImmutable::parse('2024-06-01'),
        )->execute();

        $this->assertEquals('2024-06-01', $loan->refresh()->started_on->toDateString());
    }

    #[Test]
    public function the_provenance_events_follow_the_loan(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->active()->create(['possession_id' => $possession->id]);

        new UpdatePossessionLoan(
            user: $user,
            loan: $loan,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Returned,
            startedOn: CarbonImmutable::parse('2024-06-12'),
            returnedOn: CarbonImmutable::parse('2024-06-15'),
            isInProvenance: true,
        )->execute();

        $this->assertNotNull($loan->departure_provenance_event_id);
        $this->assertNotNull($loan->return_provenance_event_id);
        $this->assertDatabaseCount('possession_provenance_events', 2);

        new UpdatePossessionLoan(
            user: $user,
            loan: $loan,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Active,
            startedOn: CarbonImmutable::parse('2024-06-11'),
            isInProvenance: true,
        )->execute();

        $this->assertNull($loan->return_provenance_event_id);
        $this->assertEquals('2024-06-11', PossessionProvenanceEvent::query()->sole()->occurred_on?->toDateString());

        new UpdatePossessionLoan(
            user: $user,
            loan: $loan,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Active,
            startedOn: CarbonImmutable::parse('2024-06-11'),
        )->execute();

        $this->assertNull($loan->departure_provenance_event_id);
        $this->assertDatabaseCount('possession_provenance_events', 0);
    }

    #[Test]
    public function it_refuses_a_loan_of_another_account(): void
    {
        $user = User::factory()->create();
        $loan = PossessionLoan::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionLoan(
            user: $user,
            loan: $loan,
            direction: PossessionLoanDirectionEnum::Lent,
            status: PossessionLoanStatusEnum::Active,
            startedOn: CarbonImmutable::parse('2024-06-12'),
        )->execute();
    }
}
