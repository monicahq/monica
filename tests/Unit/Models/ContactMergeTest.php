<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactMergeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_still_reaches_the_absorbed_contact_once_it_is_soft_deleted(): void
    {
        $merge = ContactMerge::factory()->create();
        $merge->mergedContact->delete();

        $this->assertInstanceOf(Contact::class, $merge->fresh()->mergedContact);
    }

    #[Test]
    public function it_stands_until_it_is_undone(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(ContactMerge::factory()->create()->stands());
        $this->assertFalse(ContactMerge::factory()->undoneBy($user)->create()->stands());
    }

    #[Test]
    public function it_is_addressed_by_its_uuid(): void
    {
        $merge = ContactMerge::factory()->create();

        $this->assertEquals('uuid', $merge->getRouteKeyName());
        $this->assertEquals($merge->uuid, $merge->getRouteKey());
    }

    #[Test]
    public function it_does_not_resolve_a_merge_of_another_account(): void
    {
        $account = Account::factory()->create();
        $user = $this->assignUserToAccount(user: User::factory()->create(), account: $account);
        $merge = ContactMerge::factory()->create();

        $this->actingAs($user);

        $this->assertNull(new ContactMerge()->resolveRouteBinding($merge->uuid));
    }

    #[Test]
    public function it_resolves_a_merge_of_the_account_asking(): void
    {
        $merge = ContactMerge::factory()->create();
        $user = $this->assignUserToAccount(
            user: User::factory()->create(),
            account: Account::query()->findOrFail($merge->account_id),
        );

        $this->actingAs($user);

        $this->assertEquals($merge->id, new ContactMerge()->resolveRouteBinding($merge->uuid)?->id);
    }

    #[Test]
    public function it_does_not_resolve_through_a_vault_it_is_not_in(): void
    {
        $merge = ContactMerge::factory()->create();
        $account = Account::query()->findOrFail($merge->account_id);
        $user = $this->assignUserToAccount(user: User::factory()->create(), account: $account);
        $otherVault = Vault::factory()->create(['account_id' => $account->id]);

        $this->actingAs($user);

        $this->assertNull(
            ContactMerge::query()
                ->where('uuid', $merge->uuid)
                ->where('vault_id', $otherVault->id)
                ->first()
        );
    }
}
