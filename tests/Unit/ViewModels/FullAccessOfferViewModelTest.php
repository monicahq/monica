<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\FullAccessOfferViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FullAccessOfferViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_people_before_vaults(): void
    {
        $user = User::factory()->create();

        $meters = new FullAccessOfferViewModel(user: $user)->meters();

        $this->assertSame(['People', 'Vaults'], array_column($meters, 'label'));
    }

    #[Test]
    public function it_counts_the_people_the_free_plan_measures(): void
    {
        config(['monica.free_contact_limit' => 30]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->count(2)->create(['vault_id' => $vault->id, 'first_name' => 'Dwight']);
        Contact::factory()->me()->create(['vault_id' => $vault->id]);
        Contact::factory()->partial()->create(['vault_id' => $vault->id]);

        $people = new FullAccessOfferViewModel(user: $user)->meters()[0];

        $this->assertSame($user->account->contactCount(), $people['used']);
        $this->assertSame(2, $people['used']);
        $this->assertSame(30, $people['limit']);
    }

    #[Test]
    public function it_counts_the_vaults_of_the_account(): void
    {
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        Vault::factory()->create(['name' => 'Michael Scott Paper Company']);

        $vaults = new FullAccessOfferViewModel(user: $user)->meters()[1];

        $this->assertSame(1, $vaults['used']);
        $this->assertSame(1, $vaults['limit']);
    }

    #[Test]
    public function it_stays_neutral_below_four_fifths_of_the_limit(): void
    {
        $people = $this->peopleMeter(held: 23, limit: 30);

        $this->assertSame('neutral', $people['tone']);
        $this->assertSame(77, $people['percent']);
        $this->assertSame('23 of 30', $people['description']);
    }

    #[Test]
    public function it_warns_from_four_fifths_of_the_limit(): void
    {
        $people = $this->peopleMeter(held: 24, limit: 30);

        $this->assertSame('warning', $people['tone']);
        $this->assertSame('24 of 30, close to the limit', $people['description']);
    }

    #[Test]
    public function it_turns_to_danger_at_the_limit(): void
    {
        $people = $this->peopleMeter(held: 30, limit: 30);

        $this->assertSame('danger', $people['tone']);
        $this->assertSame(100, $people['percent']);
        $this->assertSame('30 of 30, the limit is reached', $people['description']);
    }

    #[Test]
    public function it_caps_the_meter_above_the_limit(): void
    {
        $people = $this->peopleMeter(held: 34, limit: 30);

        $this->assertSame(34, $people['used']);
        $this->assertSame(100, $people['percent']);
        $this->assertSame('danger', $people['tone']);
    }

    #[Test]
    public function it_turns_to_danger_when_the_limit_is_zero(): void
    {
        $people = $this->peopleMeter(held: 0, limit: 0);

        $this->assertSame(100, $people['percent']);
        $this->assertSame('danger', $people['tone']);
    }

    /** @return array{label: string, used: int, limit: int, percent: int, tone: string, description: string} */
    private function peopleMeter(int $held, int $limit): array
    {
        config(['monica.free_contact_limit' => $limit]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->count($held)->create(['vault_id' => $vault->id]);

        return new FullAccessOfferViewModel(user: $user)->meters()[0];
    }
}
