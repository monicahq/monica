<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactLoansBlock;
use App\ViewModels\Vaults\ContactViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactLoansBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_loans_of_the_contact_newest_first(): void
    {
        $this->travelTo(CarbonImmutable::parse('2024-07-01 10:00'));
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['vault_id' => $vault->id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'World’s Best Boss mug']);
        $stapler = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Stapler']);
        PossessionLoan::factory()->returned()->create(['possession_id' => $stapler->id, 'contact_id' => $dwight->id, 'started_on' => '2024-01-02', 'returned_on' => '2024-01-05']);
        PossessionLoan::factory()->active()->create(['possession_id' => $mug->id, 'contact_id' => $dwight->id, 'started_on' => '2024-06-12', 'due_on' => '2024-06-15']);
        PossessionLoan::factory()->create(['possession_id' => $mug->id]);

        $loans = new ContactLoansBlock(user: $user, contact: $dwight)->loans();

        $this->assertSame(['World’s Best Boss mug', 'Stapler'], array_column($loans, 'possession'));
        $this->assertSame(['Overdue', 'Returned'], array_column($loans, 'status'));
        $this->assertSame(route('possessions.loans.index', [$vault, $mug]), $loans[0]['url']);
    }

    #[Test]
    public function the_contact_page_shows_no_block_without_a_loan(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->assertNull(new ContactViewModel(user: $user, vault: $vault, contact: $toby)->loans());
    }
}
