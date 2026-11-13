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

class ContactMarkdownControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_with_the_document_itself_rather_than_with_json(): void
    {
        Queue::fake();

        [$user, $vault, $contact] = $this->contactHoldingASecret();

        $response = $this->withToken($user->createToken('Dwight’s script')->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/markdown")
            ->assertStatus(200)
            ->assertHeader('content-type', 'text/markdown; charset=utf-8');

        $this->assertStringContainsString("{$contact->slug}.md", (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('# Dwight Schrute', $response->streamedContent());
    }

    #[Test]
    public function it_hands_a_sensitive_value_to_a_key_minted_to_reach_it(): void
    {
        Queue::fake();

        [$user, $vault, $contact] = $this->contactHoldingASecret();

        $document = $this->withToken($user->createToken('Scoped', ['sensitive-fields'])->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/markdown")
            ->streamedContent();

        $this->assertStringContainsString('Mose knows', $document);
    }

    #[Test]
    public function it_keeps_a_sensitive_value_from_a_key_minted_for_everything_else(): void
    {
        Queue::fake();

        [$user, $vault, $contact] = $this->contactHoldingASecret();

        $document = $this->withToken($user->createToken('Unscoped', ['*'])->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/markdown")
            ->streamedContent();

        $this->assertStringNotContainsString('Mose knows', $document);
    }

    #[Test]
    public function it_hands_over_exactly_what_the_interface_downloads(): void
    {
        Queue::fake();
        $this->freezeTime();

        [$user, $vault, $contact] = $this->contactHoldingASecret();

        $throughTheApi = $this->withToken($user->createToken('Scoped', ['sensitive-fields'])->plainTextToken)
            ->get("/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/markdown")
            ->streamedContent();

        $throughTheScreen = $this->actingAs($user, 'web')
            ->get(route('contacts.markdown.show', ['vault' => $vault, 'contact' => $contact]))
            ->streamedContent();

        $this->assertSame($throughTheScreen, $throughTheApi);
    }

    #[Test]
    public function it_answers_not_found_for_a_contact_reached_through_the_wrong_vault(): void
    {
        [$user, , $contact] = $this->contactHoldingASecret();
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->withToken($user->createToken('Dwight’s script')->plainTextToken)
            ->get("/api/v1/vaults/{$elsewhere->uuid}/contacts/{$contact->uuid}/markdown")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function contactHoldingASecret(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = Structure::withFields($user, [['type' => FieldTypeEnum::Text, 'label' => 'Hiding spot', 'is_sensitive' => true]]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);

        FieldValue::factory()->create([
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => Field::query()->where('label', 'Hiding spot')->sole()->id,
            'value_string' => 'Mose knows',
        ]);

        return [$user, $vault, $contact];
    }
}
