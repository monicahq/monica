<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AbsorbedContactControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_address_of_an_absorbed_contact_names_who_absorbed_them(): void
    {
        [$user, $vault, $kept, $absorbed] = $this->merged();

        $this->actingAs($user)
            ->get(url("/vaults/{$vault->slug}/contacts/{$absorbed->slug}"))
            ->assertStatus(200)
            ->assertSee($kept->name)
            ->assertSee(route('contacts.show', ['vault' => $vault, 'contact' => $kept]), escape: false);
    }

    #[Test]
    public function the_identifier_of_an_absorbed_contact_lands_on_the_same_explanation(): void
    {
        [$user, $vault, , $absorbed] = $this->merged();

        $this->actingAs($user)
            ->get(url("/vaults/{$vault->slug}/contacts/{$absorbed->uuid}"))
            ->assertStatus(200);
    }

    #[Test]
    public function a_contact_whose_merge_was_undone_is_not_found_here(): void
    {
        [$user, $vault, , $absorbed] = $this->merged();

        ContactMerge::query()->where('merged_contact_id', $absorbed->id)->update(['undone_at' => now()]);

        $this->actingAs($user)
            ->get(url("/vaults/{$vault->slug}/contacts/{$absorbed->slug}"))
            ->assertNotFound();
    }

    #[Test]
    public function an_address_nobody_ever_held_is_not_found(): void
    {
        [$user, $vault] = $this->merged();

        $this->actingAs($user)
            ->get(url("/vaults/{$vault->slug}/contacts/nobody-at-all"))
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_explain_a_merge_of_another_account(): void
    {
        [, $vault, , $absorbed] = $this->merged();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(url("/vaults/{$vault->slug}/contacts/{$absorbed->slug}"))
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: Contact} */
    private function merged(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $kept = Contact::factory()->create([
            'account_id' => $user->account_id, 'vault_id' => $vault->id,
            'first_name' => 'Pam', 'last_name' => 'Halpert',
        ]);
        $absorbed = Contact::factory()->create([
            'account_id' => $user->account_id, 'vault_id' => $vault->id,
            'first_name' => 'Pam', 'last_name' => 'Beesly',
        ]);

        ContactMerge::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'kept_contact_id' => $kept->id,
            'merged_contact_id' => $absorbed->id,
        ]);

        $absorbed->delete();

        return [$user, $vault, $kept, $absorbed];
    }
}
