<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\AddSupportNote;
use App\Mcp\Tools\ChangeSupportRequestStatus;
use App\Mcp\Tools\CreateContact;
use App\Mcp\Tools\GetContact;
use App\Mcp\Tools\GetSupportQueueRequest;
use App\Mcp\Tools\ListContacts;
use App\Mcp\Tools\ListSupportQueue;
use App\Mcp\Tools\ListTemplateVersions;
use App\Mcp\Tools\ListVaults;
use App\Mcp\Tools\RemoveTemplateSection;
use App\Mcp\Tools\SendSupportReply;
use App\Mcp\Tools\SetContactFavourite;
use App\Models\Contact;
use App\Models\EmailSent;
use App\Models\EntityType;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Location;
use App\Models\Log;
use App\Models\PossessionCondition;
use App\Models\PossessionTag;
use App\Models\PossessionType;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\SupportRequest;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Mcp\Server\Tool;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class McpCompartmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_only_the_vaults_of_the_account_the_key_belongs_to(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        Vault::factory()->create(['name' => 'Vance Refrigeration']);

        MonicaServer::actingAs($user)->tool(ListVaults::class)
            ->assertOk()
            ->assertSee('Dunder Mifflin')
            ->assertDontSee('Vance Refrigeration');
    }

    #[Test]
    public function it_reports_a_vault_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $other = Vault::factory()->create();

        MonicaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $other->uuid])
            ->assertHasErrors();
    }

    #[Test]
    public function it_reports_a_contact_of_another_vault_of_the_same_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $asked = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(GetContact::class, [
            'vault' => $asked->uuid,
            'contact' => $contact->uuid,
        ])->assertHasErrors();
    }

    #[Test]
    public function it_reports_a_template_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $other = Template::factory()->create();

        MonicaServer::actingAs($user)->tool(ListTemplateVersions::class, ['template' => $other->uuid])
            ->assertHasErrors();
    }

    #[Test]
    public function it_writes_nothing_into_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $other = Vault::factory()->create();

        MonicaServer::actingAs($user)->tool(CreateContact::class, [
            'vault' => $other->uuid,
            'first_name' => 'Michael',
        ])->assertHasErrors();

        $this->assertDatabaseMissing('contacts', ['first_name' => 'Michael', 'vault_id' => $other->id]);
    }

    #[Test]
    public function it_does_not_favourite_a_contact_of_another_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $asked = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(SetContactFavourite::class, [
            'vault' => $asked->uuid,
            'contact' => $contact->uuid,
            'favourite' => true,
        ])->assertHasErrors();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_favourite' => false]);
    }

    #[Test]
    public function it_reports_a_section_of_another_template_as_not_existing(): void
    {
        $user = User::factory()->create();
        $asked = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $asked->structure->id]);
        $elsewhere = Template::factory()->create(['account_id' => $user->account_id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => StructureVersion::factory()->create(['structure_id' => $elsewhere->structure->id])->id,
            'section_version_id' => SectionVersion::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        MonicaServer::actingAs($user)->tool(RemoveTemplateSection::class, [
            'template' => $asked->uuid,
            'version' => 1,
            'section' => (string) $row->id,
        ])->assertHasErrors();

        $this->assertModelExists($row);
    }

    /** @param  class-string<Tool>  $tool */
    #[Test]
    #[DataProvider('registeredTools')]
    public function every_tool_reports_a_record_of_another_account_as_not_existing(string $tool): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $schema = app($tool)->toArray()['inputSchema'];
        $required = $schema['required'] ?? [];
        $parameter = in_array('vault', $required, true) ? 'vault' : ($required[0] ?? null);
        $factory = self::recordsOfAnotherAccount()[$parameter] ?? null;

        if ($factory === null) {
            $this->markTestSkipped("{$tool} addresses no record of an account.");
        }

        $record = $factory();
        $arguments = [$parameter => (string) ($record->getAttribute('uuid') ?? $record->getKey())];

        foreach ($required as $name) {
            if (! array_key_exists($name, $arguments)) {
                $arguments[$name] = self::placeholder($name, $schema['properties'][$name] ?? []);
            }
        }

        MonicaServer::actingAs($user)->tool($tool, $arguments)->assertHasErrors(['does not exist']);
    }

    /** @return array<string, array{class-string<Tool>}> */
    public static function registeredTools(): array
    {
        $tools = new ReflectionClass(MonicaServer::class)->getDefaultProperties()['tools'];
        $administration = [GetSupportQueueRequest::class, SendSupportReply::class, AddSupportNote::class, ChangeSupportRequestStatus::class, ListSupportQueue::class];

        return collect($tools)
            ->reject(fn (string $tool): bool => in_array($tool, $administration, true))
            ->mapWithKeys(fn (string $tool): array => [class_basename($tool) => [$tool]])
            ->all();
    }

    /** @return array<string, Closure(): Model> */
    private static function recordsOfAnotherAccount(): array
    {
        return [
            'vault' => fn (): Model => Vault::factory()->create(),
            'template' => fn (): Model => Template::factory()->create(),
            'entity_type' => fn (): Model => EntityType::factory()->create(),
            'location' => fn (): Model => Location::factory()->create(),
            'possession_type' => fn (): Model => PossessionType::factory()->create(),
            'possession_tag' => fn (): Model => PossessionTag::factory()->create(),
            'possession_condition' => fn (): Model => PossessionCondition::factory()->create(),
            'life_event_category' => fn (): Model => LifeEventCategory::factory()->create(),
            'life_event_category_id' => fn (): Model => LifeEventCategory::factory()->create(),
            'life_event_type' => fn (): Model => LifeEventType::factory()->create(),
            'relationship_category' => fn (): Model => RelationshipCategory::factory()->create(),
            'relationship_category_id' => fn (): Model => RelationshipCategory::factory()->create(),
            'relationship_type' => fn (): Model => RelationshipType::factory()->create(),
            'support_request' => fn (): Model => SupportRequest::factory()->create(),
            'log' => fn (): Model => Log::factory()->create(),
            'email_sent' => fn (): Model => EmailSent::factory()->create(),
        ];
    }

    /** @param  array<string, mixed>  $property */
    private static function placeholder(string $name, array $property): mixed
    {
        if (isset($property['enum'])) {
            return $property['enum'][0];
        }

        return match (true) {
            str_ends_with($name, '_on') => now()->addDay()->toDateString(),
            $name === 'time' => '09:00',
            ($property['type'] ?? null) === 'boolean' => true,
            ($property['type'] ?? null) === 'integer', ($property['type'] ?? null) === 'number' => 1,
            ($property['type'] ?? null) === 'array' => ['Dwight'],
            ($property['type'] ?? null) === 'object' => ['name' => 'Dwight'],
            default => 'Dwight',
        };
    }
}
