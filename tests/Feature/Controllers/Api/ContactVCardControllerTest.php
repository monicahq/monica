<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
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

        $throughTheScreen = $this->actingAs($user)
            ->get(route('contacts.vcard.show', ['vault' => $vault, 'contact' => $contact]))
            ->streamedContent();

        $this->assertSame($throughTheScreen, $throughTheApi);
        $this->assertStringContainsString('+15705551212', $throughTheApi);
    }

    #[Test]
    public function it_answers_not_found_for_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $stranger = Contact::factory()->create();

        $this->withToken($user->createToken('Michael’s script')->plainTextToken)
            ->getJson("/api/v1/vaults/{$stranger->vault->uuid}/contacts/{$stranger->uuid}/vcard")
            ->assertNotFound();
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

    #[Test]
    public function it_refuses_a_caller_carrying_no_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        $this->getJson("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/vcard")
            ->assertUnauthorized();
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
