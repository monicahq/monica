<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Actions\MergeContacts;
use App\Actions\PreviewContactMerge;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactMergeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_the_other_contacts_of_the_vault(): void
    {
        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        $response = $this->actingAs($user)->get(
            route('contacts.merge.new', ['vault' => $jim->vault, 'contact' => $jim])
        );

        $response->assertStatus(200);
        $response->assertSee('James Halpert');
    }

    #[Test]
    public function it_does_not_offer_the_contact_itself(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault, $this->publishedContactVersion($user), 'Jim', 'Halpert');

        $response = $this->actingAs($user)->get(
            route('contacts.merge.new', ['vault' => $vault, 'contact' => $contact])
        );

        $response->assertStatus(200);
        $response->assertSee('Nobody to merge with');
    }

    #[Test]
    public function it_shows_both_sides_of_a_difference(): void
    {
        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $this->valueFor($james, $field, 'james@dundermifflin.com');

        $response = $this->actingAs($user)->get(
            route('contacts.merge.show', ['vault' => $jim->vault, 'contact' => $jim, 'other' => $james])
        );

        $response->assertStatus(200);
        $response->assertSee('jim@dundermifflin.com');
        $response->assertSee('james@dundermifflin.com');
        $response->assertSee('This absorbs a contact');
    }

    #[Test]
    public function it_opens_either_record_from_the_screen(): void
    {
        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        $response = $this->actingAs($user)->get(
            route('contacts.merge.show', ['vault' => $jim->vault, 'contact' => $jim, 'other' => $james])
        );

        $response->assertStatus(200);
        $response->assertSee('The two records');
        $response->assertSee(route('contacts.show', ['vault' => $jim->vault, 'contact' => $jim]));
        $response->assertSee(route('contacts.show', ['vault' => $jim->vault, 'contact' => $james]));
    }

    #[Test]
    public function it_merges_two_contacts(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $fingerprint = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute()['fingerprint'];

        $response = $this->actingAs($user)->post(
            route('contacts.merge.create', ['vault' => $jim->vault, 'contact' => $jim, 'other' => $james]),
            ['fingerprint' => $fingerprint]
        );

        $response->assertRedirect(route('contacts.show', ['vault' => $jim->vault, 'contact' => $jim]));
        $response->assertSessionHas('status');
        $this->assertSoftDeleted($james);
    }

    #[Test]
    public function it_tells_the_reader_of_an_absorbed_address_who_absorbed_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        $this->actingAs($user)->post(
            route('contacts.merge.create', ['vault' => $jim->vault, 'contact' => $jim, 'other' => $james]),
            ['fingerprint' => new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute()['fingerprint']]
        );

        $response = $this->actingAs($user)->get(
            route('contacts.show', ['vault' => $jim->vault, 'contact' => $james->uuid])
        );

        $response->assertStatus(200)
            ->assertSee('This record was merged')
            ->assertSee(route('contacts.show', ['vault' => $jim->vault, 'contact' => $jim]), escape: false);
    }

    #[Test]
    public function it_refuses_a_merge_confirmed_against_a_state_that_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $value = $this->valueFor($james, $field, 'james@dundermifflin.com');
        $stale = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute()['fingerprint'];

        $value->value_string = 'jamesh@dundermifflin.com';
        $value->save();

        $response = $this->actingAs($user)->post(
            route('contacts.merge.create', ['vault' => $jim->vault, 'contact' => $jim, 'other' => $james]),
            ['fingerprint' => $stale]
        );

        $response->assertRedirect(route('contacts.merge.show', ['vault' => $jim->vault, 'contact' => $jim, 'other' => $james]));
        $response->assertSessionHasErrors('fingerprint');
        $this->assertNotSoftDeleted($james);
    }

    #[Test]
    public function it_does_not_find_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault, $this->publishedContactVersion($user), 'Jim', 'Halpert');
        $stranger = Contact::factory()->create();

        $response = $this->actingAs($user)->get(
            route('contacts.merge.show', ['vault' => $vault, 'contact' => $contact, 'other' => $stranger])
        );

        $response->assertStatus(404);
    }

    #[Test]
    public function it_does_not_find_a_contact_reached_through_the_wrong_vault(): void
    {
        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $elsewhere = $this->contactIn($otherVault, $version, 'Pam', 'Beesly');

        $response = $this->actingAs($user)->get(
            route('contacts.merge.show', ['vault' => $vault, 'contact' => $contact, 'other' => $elsewhere])
        );

        $response->assertStatus(404);
    }

    #[Test]
    public function it_does_not_find_the_screen_for_a_visitor(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault, $this->publishedContactVersion($user), 'Jim', 'Halpert');

        $this->get(route('contacts.merge.new', ['vault' => $vault, 'contact' => $contact]))
            ->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_still_answers_not_found_for_a_contact_that_was_never_merged(): void
    {
        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $james->delete();

        $response = $this->actingAs($user)->get(
            route('contacts.show', ['vault' => $jim->vault, 'contact' => $james->uuid])
        );

        $response->assertStatus(404);
    }

    #[Test]
    public function it_undoes_a_merge(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $response = $this->actingAs($user)->delete(
            route('vaults.merges.destroy', ['vault' => $jim->vault, 'contactMerge' => $merge])
        );

        $response->assertRedirect(route('contacts.show', ['vault' => $jim->vault, 'contact' => $jim]));
        $response->assertSessionHas('status');
        $this->assertNotSoftDeleted($james);
    }

    #[Test]
    public function it_offers_the_undo_on_the_survivors_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $response = $this->actingAs($user)->get(
            route('contacts.show', ['vault' => $jim->vault, 'contact' => $jim])
        );

        $response->assertStatus(200);
        $response->assertSee('Undo the merge');
        $response->assertSee('James Halpert');
    }

    #[Test]
    public function it_refuses_to_undo_a_merge_twice(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        $url = route('vaults.merges.destroy', ['vault' => $jim->vault, 'contactMerge' => $merge]);

        $this->actingAs($user)->delete($url);

        $this->actingAs($user)->delete($url)->assertSessionHasErrors('merge');
    }

    #[Test]
    public function it_does_not_find_a_merge_reached_through_the_wrong_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->delete(route('vaults.merges.destroy', ['vault' => $elsewhere, 'contactMerge' => $merge]))
            ->assertStatus(404);
    }

    /** @return array{0: Contact, 1: Contact} */
    private function twoContactsOf(User $user, ?StructureVersion $version = null): array
    {
        $version ??= $this->publishedContactVersion($user);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [
            $this->contactIn($vault, $version, 'Jim', 'Halpert'),
            $this->contactIn($vault, $version, 'James', 'Halpert'),
        ];
    }

    private function contactIn(Vault $vault, StructureVersion $version, string $first, string $last): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    private function publishedContactVersion(User $user): StructureVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        return StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
    }

    private function fieldCarriedBy(StructureVersion $version): Field
    {
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $version->account_id]);
        $field = Field::factory()->create([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->status = VersionStatusEnum::Draft;
        $version->save();

        StructureVersionSection::factory()->create([
            'account_id' => $version->account_id,
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->status = VersionStatusEnum::Published;
        $version->save();

        return $field;
    }

    private function valueFor(Contact $contact, Field $field, string $value): FieldValue
    {
        return FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => $value,
        ]);
    }
}
