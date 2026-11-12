<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_every_relationship_a_contact_is_part_of_from_either_side(): void
    {
        $vault = Vault::factory()->create();
        $judy = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);
        $ross = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);
        $monica = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);

        $asSideA = Relationship::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $ross->id,
        ]);
        $asSideB = Relationship::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'contact_a_id' => $monica->id,
            'contact_b_id' => $judy->id,
        ]);

        $found = Relationship::query()->forContact($judy)->pluck('id');

        $this->assertCount(2, $found);
        $this->assertContains($asSideA->id, $found);
        $this->assertContains($asSideB->id, $found);
    }

    #[Test]
    public function it_excludes_a_relationship_that_was_deleted(): void
    {
        $relationship = Relationship::factory()->create();
        $contact = Contact::query()->findOrFail($relationship->contact_a_id);

        $relationship->delete();

        $this->assertCount(0, Relationship::query()->forContact($contact)->get());
    }

    #[Test]
    public function it_excludes_a_relationship_in_another_vault(): void
    {
        $account = Account::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $account->id]);
        $elsewhere = Vault::factory()->create(['account_id' => $account->id]);

        $judy = Contact::factory()->create(['account_id' => $account->id, 'vault_id' => $vault->id]);
        $mine = Relationship::factory()->create([
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'contact_a_id' => $judy->id,
        ]);
        Relationship::factory()->create(['account_id' => $account->id, 'vault_id' => $elsewhere->id]);

        $found = Relationship::query()->forContact($judy)->pluck('id');

        $this->assertCount(1, $found);
        $this->assertContains($mine->id, $found);
    }
}
