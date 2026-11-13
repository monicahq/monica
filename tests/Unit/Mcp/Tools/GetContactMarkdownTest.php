<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\FieldTypeEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetContactMarkdown;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class GetContactMarkdownTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_the_contact_as_markdown(): void
    {
        Queue::fake();

        [$user, $vault, $contact] = $this->contactHoldingASecret();

        MonicaServer::actingAs($user)->tool(GetContactMarkdown::class, ['vault' => $vault->uuid, 'contact' => $contact->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('# Kevin Malone');
    }

    #[Test]
    public function it_leaves_a_sensitive_value_out_for_a_connection_without_the_scope(): void
    {
        Queue::fake();

        [$user, $vault, $contact] = $this->contactHoldingASecret();

        MonicaServer::actingAs($user)->tool(GetContactMarkdown::class, ['vault' => $vault->uuid, 'contact' => $contact->uuid])
            ->assertOk()
            ->assertDontSee('Famous chili');
    }

    #[Test]
    public function it_gives_a_sensitive_value_to_a_key_holding_the_scope(): void
    {
        Queue::fake();

        [$user, $vault, $contact] = $this->contactHoldingASecret();
        $user->withAccessToken(PersonalAccessToken::findToken($user->createToken('Kevin’s script', ['sensitive-fields'])->plainTextToken));

        MonicaServer::actingAs($user)->tool(GetContactMarkdown::class, ['vault' => $vault->uuid, 'contact' => $contact->uuid])
            ->assertOk()
            ->assertSee('Famous chili');
    }

    #[Test]
    public function it_refuses_a_contact_the_vault_does_not_hold(): void
    {
        [$user, $vault] = $this->contactHoldingASecret();
        $elsewhere = Contact::factory()->create();

        MonicaServer::actingAs($user)->tool(GetContactMarkdown::class, ['vault' => $vault->uuid, 'contact' => $elsewhere->uuid])
            ->assertHasErrors(['That contact does not exist.']);
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function contactHoldingASecret(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = Structure::withFields($user, [['type' => FieldTypeEnum::Text, 'label' => 'Recipe', 'is_sensitive' => true]]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'first_name' => 'Kevin',
            'last_name' => 'Malone',
        ]);

        FieldValue::factory()->create([
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => Field::query()->where('label', 'Recipe')->sole()->id,
            'value_string' => 'Famous chili',
        ]);

        return [$user, $vault, $contact];
    }
}
