<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionLoanContactCandidatesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionLoanContactCandidatesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_the_people_in_the_order_of_the_language_of_the_account(): void
    {
        $user = User::factory()->create(['locale' => 'fr_FR']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Erin', 'last_name' => 'Élan']);

        $candidates = new PossessionLoanContactCandidatesViewModel(user: $user, vault: $vault, query: '')->candidates();

        $this->assertSame(['Erin Élan', 'Dwight Schrute'], array_column($candidates, 'name'));
    }
}
