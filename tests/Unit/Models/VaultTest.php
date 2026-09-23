<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gets_an_unguessable_identifier_and_keeps_a_number_for_a_key(): void
    {
        $vault = Vault::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $vault->uuid,
        );
        $this->assertIsInt($vault->id);
        $this->assertEquals('slug', $vault->getRouteKeyName());
        $this->assertEquals($vault->slug, $vault->getRouteKey());
    }

    #[Test]
    public function it_holds_no_contact_and_no_visit_to_begin_with(): void
    {
        $vault = Vault::factory()->create();

        $this->assertSame(0, $vault->contact_count);
        $this->assertFalse($vault->is_default);
        $this->assertNull($vault->last_visited_at);
    }

    #[Test]
    public function it_holds_the_private_links_opened_onto_itself(): void
    {
        $vault = Vault::factory()->create();
        $ontoTheVault = ShareLink::factory()->forVault($vault)->create();
        ShareLink::factory()->create(['vault_id' => $vault->id]);

        $this->assertCount(1, $vault->shareLinks);
        $this->assertTrue($ontoTheVault->is($vault->shareLinks->first()));
    }

    #[Test]
    public function it_is_deleted_softly(): void
    {
        $vault = Vault::factory()->create(['name' => 'Schrute Farms']);

        $vault->delete();

        $this->assertSoftDeleted('vaults', ['id' => $vault->id]);
        $this->assertEquals(0, Vault::query()->count());
        $this->assertEquals(1, Vault::query()->withTrashed()->count());
    }

    #[Test]
    public function it_gets_a_readable_address_built_from_its_name(): void
    {
        $vault = Vault::factory()->create(['name' => 'Famille Halpert']);

        $this->assertSame('famille-halpert', $vault->slug);
        $this->assertDatabaseHas('vault_slug_reservations', [
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'slug' => 'famille-halpert',
        ]);
    }

    #[Test]
    public function it_numbers_a_second_vault_of_the_same_name(): void
    {
        $account = Account::factory()->create();

        $first = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Famille']);
        $second = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Famille']);

        $this->assertSame('famille', $first->slug);
        $this->assertSame('famille-2', $second->slug);
    }

    #[Test]
    public function it_lets_another_account_hold_the_same_readable_address(): void
    {
        $mine = Vault::factory()->create(['name' => 'Famille']);
        $theirs = Vault::factory()->create(['name' => 'Famille']);

        $this->assertNotSame($mine->account_id, $theirs->account_id);
        $this->assertSame('famille', $mine->slug);
        $this->assertSame('famille', $theirs->slug);
    }

    #[Test]
    public function it_refuses_a_name_a_route_already_fixes(): void
    {
        $vault = Vault::factory()->create(['name' => 'New']);

        $this->assertSame('new-2', $vault->slug);
    }

    #[Test]
    public function it_keeps_the_alphabet_a_name_is_written_in(): void
    {
        $vault = Vault::factory()->create(['name' => '家族']);

        $this->assertSame('家族', $vault->slug);
    }

    #[Test]
    public function it_gives_an_address_to_a_name_that_leaves_nothing_readable(): void
    {
        $vault = Vault::factory()->create(['name' => '🎉🎉🎉']);

        $this->assertSame('vault', $vault->slug);
    }

    #[Test]
    public function a_vault_named_with_digits_resolves_to_itself_rather_than_to_the_row_of_that_number(): void
    {
        $user = User::factory()->create();
        $decoy = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Decoy']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => (string) $decoy->id]);

        $this->actingAs($user);

        $this->assertTrue($vault->is(new Vault()->resolveRouteBinding((string) $decoy->id)));
    }

    #[Test]
    public function it_is_reached_by_its_readable_address_and_by_its_identifier(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Famille']);

        $this->actingAs($user);

        $this->assertTrue($vault->is(new Vault()->resolveRouteBinding('famille')));
        $this->assertTrue($vault->is(new Vault()->resolveRouteBinding($vault->uuid)));
    }

    #[Test]
    public function renaming_a_vault_rebuilds_its_readable_address(): void
    {
        $vault = Vault::factory()->create(['name' => 'Famille']);

        $vault->name = 'Famille Halpert';
        $vault->save();

        $this->assertSame('famille-halpert', $vault->fresh()->slug);
    }

    #[Test]
    public function a_readable_address_a_vault_has_released_is_refused_to_every_later_vault(): void
    {
        $account = Account::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Famille']);

        $vault->name = 'Amis';
        $vault->save();

        $later = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Famille']);

        $this->assertSame('amis', $vault->fresh()->slug);
        $this->assertSame('famille-2', $later->slug);
    }

    #[Test]
    public function a_save_that_touches_no_name_leaves_the_readable_address_alone(): void
    {
        $vault = Vault::factory()->create(['name' => 'Famille']);

        $vault->description = 'Everybody at home';
        $vault->save();

        $this->assertSame('famille', $vault->fresh()->slug);
    }

    #[Test]
    public function a_vault_renamed_back_takes_the_readable_address_it_held_before(): void
    {
        $vault = Vault::factory()->create(['name' => 'Famille']);

        $vault->name = 'Amis';
        $vault->save();

        $vault->name = 'Famille';
        $vault->save();

        $this->assertSame('famille', $vault->fresh()->slug);
    }
}
