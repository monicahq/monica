<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\ShareLink;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($vault->account()->exists());
        $this->assertTrue($account->is($vault->account));
    }

    #[Test]
    public function it_gets_an_unguessable_identifier_and_keeps_a_number_for_a_key(): void
    {
        $vault = Vault::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $vault->uuid,
        );
        $this->assertIsInt($vault->id);
        $this->assertEquals('uuid', $vault->getRouteKeyName());
        $this->assertEquals($vault->uuid, $vault->getRouteKey());
    }

    #[Test]
    public function it_casts_the_position_the_count_the_default_and_the_last_visit(): void
    {
        $vault = Vault::factory()->default()->create([
            'position' => 3,
            'contact_count' => 12,
            'last_visited_at' => now(),
        ]);

        $vault->refresh();

        $this->assertSame(3, $vault->position);
        $this->assertSame(12, $vault->contact_count);
        $this->assertTrue($vault->is_default);
        $this->assertInstanceOf(CarbonImmutable::class, $vault->last_visited_at);
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
    public function it_holds_the_structures_a_new_record_starts_on(): void
    {
        $vault = Vault::factory()->create();
        $default = VaultDefaultTemplate::factory()->create(['vault_id' => $vault->id]);

        $this->assertTrue($vault->defaultTemplates()->exists());
        $this->assertTrue($default->is($vault->defaultTemplates->first()));
    }

    /**
     * The links onto one of its contacts are not these: they hang off the
     * contact and carry the vault only to say where that contact lives.
     */
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
}
