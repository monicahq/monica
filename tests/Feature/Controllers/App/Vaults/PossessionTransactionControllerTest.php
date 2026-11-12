<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\PossessionTransactionTypeEnum;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_transactions_of_a_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);
        PossessionTransaction::factory()->create(['possession_id' => $dundie->id, 'counterparty' => 'Dundies Inc.']);

        $this->actingAs($user)->get(route('possessions.transactions.index', [$vault, $dundie]))
            ->assertOk()
            ->assertSee('Dundies Inc.')
            ->assertSee('aria-current="page"', escape: false)
            ->assertSee('x-data="{', escape: false)
            ->assertDontSee('@js(', escape: false);
    }

    #[Test]
    public function it_records_a_transaction(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.transactions.create', [$vault, $dundie]), [
            'type' => 'purchase',
            'occurred_on' => '2005-03-14',
            'counterparty' => 'Dundies Inc.',
            'amount' => '40',
            'shipping' => '6',
            'currency' => 'usd',
        ])
            ->assertRedirect(route('possessions.transactions.index', [$vault, $dundie]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('possession_transactions', [
            'possession_id' => $dundie->id,
            'type' => PossessionTransactionTypeEnum::Purchase->value,
            'amount' => '40.00',
            'shipping' => '6.00',
        ]);
    }

    #[Test]
    public function it_shows_every_transaction_after_recording_a_money_only_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.transactions.create', [$vault, $dundie]), [
            'type' => 'fee',
            'fees' => '85',
            'currency' => 'usd',
        ])->assertSessionHas('transactionsView', 'all');
    }

    #[Test]
    public function it_refuses_an_amount_below_zero(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.transactions.create', [$vault, $dundie]), [
            'type' => 'refund',
            'amount' => '-5',
            'currency' => 'usd',
        ])->assertSessionHasErrors('amount');
    }

    #[Test]
    public function it_returns_the_refusal_of_a_figure_without_its_currency_to_the_form(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.transactions.create', [$vault, $dundie]), [
            'type' => 'fee',
            'fees' => '85',
        ])->assertSessionHasErrors('currency');
    }

    #[Test]
    public function it_opens_a_transaction_in_the_form_to_edit_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $dundie->id, 'counterparty' => 'Dundies Inc.']);

        $this->actingAs($user)->get(route('possessions.transactions.edit', [$vault, $dundie, $purchase]))
            ->assertOk()
            ->assertSee('value="Dundies Inc."', escape: false)
            ->assertSee('action="'.route('possessions.transactions.update', [$vault, $dundie, $purchase]).'"', escape: false);
    }

    #[Test]
    public function it_changes_a_transaction(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $dundie->id, 'shipping' => '60.00']);

        $this->actingAs($user)->put(route('possessions.transactions.update', [$vault, $dundie, $purchase]), [
            'type' => 'purchase',
            'amount' => '40',
            'shipping' => '6',
            'currency' => 'usd',
        ])
            ->assertRedirect(route('possessions.transactions.index', [$vault, $dundie]))
            ->assertSessionHas('status');

        $this->assertSame('6.00', $purchase->refresh()->shipping);
    }

    #[Test]
    public function it_deletes_a_transaction(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $fee = PossessionTransaction::factory()->create(['possession_id' => $dundie->id]);

        $this->actingAs($user)->delete(route('possessions.transactions.destroy', [$vault, $dundie, $fee]))
            ->assertRedirect(route('possessions.transactions.index', [$vault, $dundie]))
            ->assertSessionHas('status');

        $this->assertModelMissing($fee);
    }

    #[Test]
    public function a_transaction_reached_through_another_possession_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $dundie->id]);

        $this->actingAs($user)->get(route('possessions.transactions.edit', [$vault, $mug, $purchase]))->assertNotFound();
        $this->actingAs($user)->delete(route('possessions.transactions.destroy', [$vault, $mug, $purchase]))->assertNotFound();
        $this->assertModelExists($purchase);
    }
}
