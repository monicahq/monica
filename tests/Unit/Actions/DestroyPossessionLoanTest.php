<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionLoan;
use App\Actions\UpdatePossessionLoan;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPossessionLoanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_loan_and_its_provenance_events_and_keeps_the_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Banjo']);
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

        new DestroyPossessionLoan(user: $user, loan: $loan)->execute();

        $this->assertModelMissing($loan);
        $this->assertDatabaseCount('possession_provenance_events', 0);
        $this->assertDatabaseHas('contacts', ['id' => $loan->contact_id, 'deleted_at' => null]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionLoanDeletion
                && $job->parameters === ['name' => 'Banjo'],
        );
    }

    #[Test]
    public function it_refuses_a_loan_of_another_account(): void
    {
        $user = User::factory()->create();
        $loan = PossessionLoan::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionLoan(user: $user, loan: $loan)->execute();
    }
}
