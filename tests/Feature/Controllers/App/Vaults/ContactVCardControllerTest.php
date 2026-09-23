<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\StructureVersion;
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
    public function it_hands_the_contact_over_as_a_card(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $response = $this->actingAs($user)
            ->get(route('contacts.vcard.show', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'text/vcard; charset=utf-8');

        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString($contact->slug.'.vcf', (string) $response->headers->get('content-disposition'));

        $card = $response->streamedContent();

        $this->assertStringContainsString('BEGIN:VCARD', $card);
        $this->assertStringContainsString('N:Scott;Michael;;;', $card);
        $this->assertStringContainsString('UID:'.$contact->uuid, $card);
    }

    #[Test]
    public function it_carries_the_values_the_reader_marked_sensitive(): void
    {
        Queue::fake();

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
        $this->valueOn($contact, $version, '+15705551212');

        $card = $this->actingAs($user)
            ->get(route('contacts.vcard.show', ['vault' => $vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->streamedContent();

        $this->assertStringContainsString('+15705551212', $card);
    }

    #[Test]
    public function it_answers_not_found_for_a_contact_reached_through_the_wrong_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('contacts.vcard.show', ['vault' => $otherVault, 'contact' => $contact]))
            ->assertStatus(404);
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }

    private function valueOn(Contact $contact, StructureVersion $version, string $value): void
    {
        $field = $version->sectionVersions()->with('fields')->get()
            ->flatMap(fn ($section) => $section->fields)
            ->values()
            ->first();

        FieldValue::factory()->create([
            'value_string' => $value,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field instanceof Field ? $field->id : null,
            'field_key' => $field instanceof Field ? $field->key : null,
        ]);
    }
}
