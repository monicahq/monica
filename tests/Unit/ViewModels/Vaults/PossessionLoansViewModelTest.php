<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\PossessionLoanDirectionEnum;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionLoan;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionLoansViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionLoansViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_no_active_loan_and_no_rows_without_a_loan(): void
    {
        [$user, $mug] = $this->mug();

        $viewModel = new PossessionLoansViewModel(user: $user, possession: $mug);

        $this->assertNull($viewModel->active());
        $this->assertSame([], $viewModel->rows());
        $this->assertSame('', $viewModel->countLine());
        $this->assertSame('New loan', $viewModel->form()['title']);
        $this->assertSame('active', $viewModel->form()['values']['status']);
    }

    #[Test]
    public function it_says_who_has_it_now_and_when_it_is_due_back(): void
    {
        $this->travelTo(CarbonImmutable::parse('2024-06-12 10:00'));
        [$user, $mug] = $this->mug();
        $dwight = $this->contact($mug, 'Dwight', 'Schrute');
        PossessionLoan::factory()->active()->create(['possession_id' => $mug->id, 'contact_id' => $dwight->id, 'started_on' => '2024-06-10', 'due_on' => '2024-06-15']);

        $active = new PossessionLoansViewModel(user: $user, possession: $mug)->active();

        $this->assertSame('Dwight Schrute', $active['name']);
        $this->assertStringStartsWith('Dwight Schrute has had it since ', $active['sentence']);
        $this->assertStringEndsWith(', in 3 days', $active['due']);
        $this->assertFalse($active['isOverdue']);
    }

    #[Test]
    public function a_borrowed_loan_past_its_due_day_is_overdue(): void
    {
        $this->travelTo(CarbonImmutable::parse('2024-06-19 10:00'));
        [$user, $mug] = $this->mug();
        $jan = $this->contact($mug, 'Jan', 'Levinson');
        PossessionLoan::factory()->active()->create(['possession_id' => $mug->id, 'contact_id' => $jan->id, 'direction' => PossessionLoanDirectionEnum::Borrowed, 'due_on' => '2024-06-15']);

        $viewModel = new PossessionLoansViewModel(user: $user, possession: $mug);

        $this->assertStringStartsWith('You have had it from Jan Levinson since ', $viewModel->active()['sentence']);
        $this->assertStringEndsWith(', 4 days ago', $viewModel->active()['due']);
        $this->assertTrue($viewModel->active()['isOverdue']);
        $this->assertSame('Overdue', $viewModel->rows()[0]['status']);
        $this->assertSame('Borrowed from', $viewModel->rows()[0]['verb']);
    }

    #[Test]
    public function it_lists_the_loans_newest_first_with_their_status_and_facts(): void
    {
        $this->travelTo(CarbonImmutable::parse('2024-07-01 10:00'));
        [$user, $mug] = $this->mug();
        $good = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Good']);
        PossessionLoan::factory()->returned()->create(['possession_id' => $mug->id, 'contact_id' => $this->contact($mug, 'Pam', 'Beesly')->id, 'started_on' => '2024-06-01', 'returned_on' => '2024-06-08', 'condition_back_id' => $good->id, 'is_in_provenance' => true]);
        PossessionLoan::factory()->planned()->create(['possession_id' => $mug->id, 'contact_id' => $this->contact($mug, 'Jim', 'Halpert')->id, 'started_on' => '2024-08-01', 'direction' => PossessionLoanDirectionEnum::Borrowed]);

        $viewModel = new PossessionLoansViewModel(user: $user, possession: $mug);
        $rows = $viewModel->rows();

        $this->assertSame(['Jim Halpert', 'Pam Beesly'], array_column($rows, 'name'));
        $this->assertSame('Planned', $rows[0]['status']);
        $this->assertStringEndsWith(', planned', $rows[0]['period']);
        $this->assertSame('No return date agreed', $rows[0]['span']);
        $this->assertSame('Returned', $rows[1]['status']);
        $this->assertSame('7 days', $rows[1]['span']);
        $this->assertTrue($rows[1]['isInProvenance']);
        $this->assertFalse($rows[1]['canReturn']);
        $this->assertSame(['Not recorded', 'Good', 'Not recorded'], array_column($rows[1]['facts'], 'value'));
        $this->assertStringContainsString('Its provenance events are deleted with it.', $rows[1]['deleteWarning']);
        $this->assertSame('2 loans, 1 lent out', $viewModel->countLine());
    }

    #[Test]
    public function a_loan_whose_contact_was_deleted_names_a_deleted_contact(): void
    {
        [$user, $mug] = $this->mug();
        $creed = $this->contact($mug, 'Creed', 'Bratton');
        PossessionLoan::factory()->returned()->create(['possession_id' => $mug->id, 'contact_id' => $creed->id]);
        $creed->delete();

        $row = new PossessionLoansViewModel(user: $user, possession: $mug)->rows()[0];

        $this->assertSame('A deleted contact', $row['name']);
        $this->assertNull($row['contactUrl']);
        $this->assertStringNotContainsString('stays in your contacts', $row['deleteWarning']);
    }

    #[Test]
    public function marking_a_loan_as_returned_opens_it_returned_today_and_leaves_it_out_of_the_list(): void
    {
        $this->travelTo(CarbonImmutable::parse('2024-06-15 10:00'));
        [$user, $mug] = $this->mug();
        $loan = PossessionLoan::factory()->active()->create(['possession_id' => $mug->id, 'contact_id' => $this->contact($mug, 'Andy', 'Bernard')->id]);

        $viewModel = new PossessionLoansViewModel(user: $user, possession: $mug, editing: $loan, isReturning: true);

        $this->assertSame('returned', $viewModel->form()['values']['status']);
        $this->assertSame('2024-06-15', $viewModel->form()['values']['returned_on']);
        $this->assertSame('Andy Bernard', $viewModel->form()['contactName']);
        $this->assertSame([], $viewModel->rows());
    }

    /** @return array{User, Possession} */
    private function mug(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'World’s Best Boss mug'])];
    }

    private function contact(Possession $possession, string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create(['vault_id' => $possession->vault_id, 'first_name' => $firstName, 'last_name' => $lastName]);
    }
}
