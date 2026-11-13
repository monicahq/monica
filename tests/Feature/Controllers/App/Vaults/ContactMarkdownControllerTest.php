<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

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
    public function it_hands_the_contact_over_as_a_markdown_file_sensitive_values_included(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = Structure::withFields($user, [['type' => FieldTypeEnum::Text, 'label' => 'Shoe size', 'is_sensitive' => true]]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
        FieldValue::factory()->create([
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => Field::query()->where('label', 'Shoe size')->sole()->id,
            'value_string' => '10 and a half',
        ]);

        $response = $this->actingAs($user)
            ->get(route('contacts.markdown.show', ['vault' => $vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'text/markdown; charset=utf-8');

        $this->assertStringContainsString("attachment; filename={$contact->slug}.md", (string) $response->headers->get('content-disposition'));

        $document = $response->streamedContent();

        $this->assertStringStartsWith('# Michael Scott', $document);
        $this->assertStringContainsString('10 and a half', $document);
    }

    #[Test]
    public function it_answers_not_found_for_a_contact_reached_through_the_wrong_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $elsewhere->id]);

        $this->actingAs($user)
            ->get(route('contacts.markdown.show', ['vault' => $vault, 'contact' => $contact]))
            ->assertNotFound();
    }
}
