<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Relationship;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillTrashOwnersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_a_merge_the_relationships_it_dropped(): void
    {
        $vault = Vault::factory()->create();
        $dropped = Relationship::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subYear()]);
        $merge = ContactMerge::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'ledger' => [['kind' => 'relationship_dropped', 'id' => $dropped->id]],
        ]);

        $this->migration()->up();

        $this->assertDatabaseHas('relationships', ['id' => $dropped->id, 'deleted_with_type' => ContactMerge::class, 'deleted_with_id' => $merge->id]);
        $this->assertTrue(Relationship::withTrashed()->find($dropped->id)->deleted_at->lt(now()->subMonth()));
    }

    #[Test]
    public function it_starts_the_retention_period_of_what_was_deleted_before_the_trash_existed(): void
    {
        $vault = Vault::factory()->create();
        $toby = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subYear()]);
        $withTheVault = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subYear(), 'deleted_with_vault' => true]);

        $this->migration()->up();

        $this->assertTrue(Contact::withTrashed()->find($toby->id)->deleted_at->isToday());
        $this->assertFalse(Contact::withTrashed()->find($withTheVault->id)->deleted_at->isToday());
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_25_000010_backfill_trash_owners.php');
    }
}
