<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Actions\MergeContacts;
use App\Actions\PreviewContactMerge;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactMergeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'kept_contact_id',
                'merged_contact_id',
                'resolutions',
                'moved',
                'stands',
                'undone_at',
                'created_at',
            ],
        ],
    ];

    /** @var array<string, mixed> */
    private array $previewStructure = [
        'data' => [
            'type',
            'attributes' => [
                'kept',
                'other',
                'conflicts',
                'moves',
                'collisions',
                'set_aside',
                'fingerprint',
            ],
        ],
    ];

    #[Test]
    public function it_previews_a_merge(): void
    {
        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $this->valueFor($james, $field, 'james@dundermifflin.com');

        Sanctum::actingAs($user);

        $response = $this->json('GET', $this->url($jim, $james));

        $response->assertOk();
        $response->assertJsonStructure($this->previewStructure);
        $response->assertJsonPath('data.attributes.kept.uuid', $jim->uuid);
        $response->assertJsonCount(1, 'data.attributes.conflicts');
    }

    #[Test]
    public function it_merges_two_contacts(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        Sanctum::actingAs($user);

        $response = $this->json('POST', $this->url($jim, $james));

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.kept_contact_id', $jim->uuid);
        $response->assertJsonPath('data.attributes.stands', true);
        $this->assertSoftDeleted($james);
    }

    #[Test]
    public function it_reports_what_moved(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $this->valueFor($james, $field, 'james@dundermifflin.com');

        Sanctum::actingAs($user);

        $response = $this->json('POST', $this->url($jim, $james));

        $response->assertCreated();
        $response->assertJsonCount(1, 'data.attributes.moved');
    }

    #[Test]
    public function it_refuses_a_resolution_the_preview_never_reported(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        Sanctum::actingAs($user);

        $response = $this->json('POST', $this->url($jim, $james), [
            'resolutions' => [['field_key' => 'invented', 'row' => null, 'keep' => 'kept']],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('resolutions');
    }

    #[Test]
    public function it_refuses_a_resolution_it_cannot_read(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        Sanctum::actingAs($user);

        $response = $this->json('POST', $this->url($jim, $james), [
            'resolutions' => [['field_key' => 'whatever', 'keep' => 'neither']],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('resolutions.0.keep');
    }

    #[Test]
    public function it_reports_a_conflict_when_the_state_changed_under_the_preview(): void
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

        Sanctum::actingAs($user);

        $response = $this->json('POST', $this->url($jim, $james), ['fingerprint' => $stale]);

        $response->assertStatus(409);
        $this->assertNotSoftDeleted($james);
    }

    #[Test]
    public function it_refuses_absorbing_the_contact_representing_the_user(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $me = Contact::factory()->create([
            'vault_id' => $vault->id,
            'template_version_id' => $version->id,
            'is_me' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', $this->url($contact, $me));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('other');
    }

    #[Test]
    public function it_does_not_find_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $stranger = Contact::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', $this->url($contact, $stranger))->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = Contact::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', $this->url($stranger, $stranger))->assertNotFound();
    }

    #[Test]
    public function it_undoes_a_merge(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        Sanctum::actingAs($user);

        $response = $this->json('DELETE', "/api/v1/vaults/{$jim->vault->uuid}/merges/{$merge->uuid}");

        $response->assertNoContent();
        $this->assertNotSoftDeleted($james);
    }

    #[Test]
    public function it_refuses_to_undo_a_merge_twice(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        $url = "/api/v1/vaults/{$jim->vault->uuid}/merges/{$merge->uuid}";

        Sanctum::actingAs($user);

        $this->json('DELETE', $url)->assertNoContent();
        $this->json('DELETE', $url)->assertUnprocessable()->assertJsonValidationErrors('merge');
    }

    #[Test]
    public function it_does_not_find_a_merge_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $stranger = ContactMerge::factory()->create();

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/merges/{$stranger->uuid}")->assertNotFound();
    }

    private function url(Contact $kept, Contact $other): string
    {
        return "/api/v1/vaults/{$kept->vault->uuid}/contacts/{$kept->uuid}/merge/{$other->uuid}";
    }

    /** @return array{0: Contact, 1: Contact} */
    private function twoContactsOf(User $user, ?TemplateVersion $version = null): array
    {
        $version ??= $this->publishedContactVersion($user);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [
            $this->contactIn($vault, $version, 'Jim', 'Halpert'),
            $this->contactIn($vault, $version, 'James', 'Halpert'),
        ];
    }

    private function contactIn(Vault $vault, TemplateVersion $version, string $first, string $last): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'template_version_id' => $version->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    private function publishedContactVersion(User $user): TemplateVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        return TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
    }

    private function fieldCarriedBy(TemplateVersion $version): Field
    {
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $version->account_id]);
        $field = Field::factory()->create([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->status = VersionStatusEnum::Draft;
        $version->save();

        TemplateVersionSection::factory()->create([
            'account_id' => $version->account_id,
            'template_version_id' => $version->id,
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
