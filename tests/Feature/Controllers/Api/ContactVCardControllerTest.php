<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Actions\ImportVCard;
use App\Enums\AddressLabelEnum;
use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class ContactVCardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_with_the_card_itself_rather_than_with_json(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $response = $this->withToken($user->createToken('Michael’s script')->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/vcard");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/vcard; charset=utf-8');

        $this->assertStringContainsString($contact->slug.'.vcf', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('BEGIN:VCARD', $response->streamedContent());
        $this->assertStringContainsString('N:Scott;Michael;;;', $response->streamedContent());
    }

    #[Test]
    public function it_hands_a_sensitive_value_to_a_key_minted_to_reach_it(): void
    {
        Queue::fake();

        [$user, $vault, $contact] = $this->contactHoldingASecret();

        $card = $this->withToken($user->createToken('Michael’s script', ['sensitive-fields'])->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/vcard")
            ->assertStatus(200)
            ->streamedContent();

        $this->assertStringContainsString('+15705551212', $card);
    }

    #[Test]
    public function it_keeps_a_sensitive_value_from_a_key_that_was_not_minted_to_reach_it(): void
    {
        Queue::fake();

        [$user, $vault, $contact] = $this->contactHoldingASecret();

        $card = $this->withToken($user->createToken('Michael’s script')->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/vcard")
            ->assertStatus(200)
            ->streamedContent();

        $this->assertStringNotContainsString('+15705551212', $card);
    }

    #[Test]
    public function a_key_minted_for_everything_still_does_not_reach_a_sensitive_value(): void
    {
        Queue::fake();

        [$user, $vault, $contact] = $this->contactHoldingASecret();

        $card = $this->withToken($user->createToken('Michael’s script', ['*'])->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/vcard")
            ->assertStatus(200)
            ->streamedContent();

        $this->assertStringNotContainsString('+15705551212', $card);
    }

    #[Test]
    public function it_hands_over_exactly_what_the_interface_downloads(): void
    {
        Queue::fake();
        $this->freezeTime();

        [$user, $vault, $contact] = $this->contactHoldingASecret();

        $throughTheApi = $this->withToken($user->createToken('Michael’s script', ['sensitive-fields'])->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/vcard")
            ->streamedContent();

        $throughTheScreen = $this->actingAs($user, 'web')
            ->get(route('contacts.vcard.show', ['vault' => $vault, 'contact' => $contact]))
            ->streamedContent();

        $this->assertSame($throughTheScreen, $throughTheApi);
        $this->assertStringContainsString('+15705551212', $throughTheApi);
    }

    #[Test]
    public function a_label_leaves_on_the_card_and_comes_back_as_the_label_it_left_with(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = Structure::asTheDefaultOf($user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone'],
        ]);
        $field = $version->sectionVersions()->with('fields')->get()->flatMap(fn ($section) => $section->fields)->first();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $work = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'key' => 'work',
            'label' => 'Work',
        ]);
        $field->value_list_id = $list->id;
        $field->save();

        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);
        FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => '+15705551212',
            'value_list_item_id' => $work->id,
        ]);

        $exported = $this->withToken($user->createToken('Dwight’s script')->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/vcard")
            ->streamedContent();

        $this->assertStringContainsString('TYPE=work', $exported);

        $returned = new ImportVCard(user: $user, vault: $vault, card: $exported, davUri: 'dwight.vcf')->execute();

        $this->assertEquals($work->id, FieldValue::query()
            ->where('valuable_id', $returned->id)
            ->where('field_id', $field->id)
            ->value('value_list_item_id'));
    }

    #[Test]
    public function an_address_a_device_wrote_survives_an_edit_on_the_screen_and_leaves_as_it_arrived(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = Structure::asTheDefaultOf($user, $vault, [
            ['type' => FieldTypeEnum::Address, 'label' => 'Address'],
        ]);
        $field = $version->sectionVersions()->with('fields')->get()->flatMap(fn ($section) => $section->fields)->first();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'key' => AddressLabelEnum::Work->value,
            'label' => 'Work',
        ]);
        $field->value_list_id = $list->id;
        $field->save();

        $sent = "BEGIN:VCARD\r\n".
            "VERSION:4.0\r\n".
            "UID:dwight\r\n".
            "N:Schrute;Dwight;;;\r\n".
            "FN:Dwight Schrute\r\n".
            "ADR;TYPE=work:PO Box 12;Suite 3;1725 Slough Avenue;Scranton;PA;18505;USA\r\n".
            "END:VCARD\r\n";
        $dwight = new ImportVCard(user: $user, vault: $vault, card: $sent, davUri: 'dwight.vcf')->execute();
        $stored = FieldValue::query()->where('valuable_id', $dwight->id)->where('field_id', $field->id)->sole();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', [
                    'vault' => $vault,
                    'contact' => $dwight,
                    'structureVersionSection' => StructureVersionSection::query()->where('section_version_id', $field->section_version_id)->sole(),
                ]),
                [
                    'fields' => [$field->id => [...$stored->value_json, 'city' => 'Stamford', 'country' => 'keep']],
                    'labels' => [$field->id => (string) $stored->value_list_item_id],
                ],
            )
            ->assertSessionHasNoErrors();

        $exported = $this->withToken($user->createToken('Dwight’s script')->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$dwight->uuid}/vcard")
            ->streamedContent();

        $this->assertStringContainsString('ADR;TYPE=work:PO Box 12;Suite 3;1725 Slough Avenue;Stamford;PA;18505;USA', $exported);
    }

    #[Test]
    public function it_answers_not_found_for_a_contact_reached_through_the_wrong_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        $this->withToken($user->createToken('Michael’s script')->plainTextToken)
            ->getJson("/api/v1/vaults/{$otherVault->uuid}/contacts/{$contact->uuid}/vcard")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function contactHoldingASecret(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = Structure::asTheDefaultOf($user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        $field = $version->sectionVersions()->with('fields')->get()
            ->flatMap(fn ($section) => $section->fields)
            ->values()
            ->first();

        FieldValue::factory()->create([
            'value_string' => '+15705551212',
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field instanceof Field ? $field->id : null,
            'field_key' => $field instanceof Field ? $field->key : null,
        ]);

        return [$user, $vault, $contact];
    }
}
