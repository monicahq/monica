<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\SectionVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactTemplatePreviewControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'attributes' => [
                'is_change',
                'from' => ['template', 'template_name', 'structure_version_id', 'version_number'],
                'to' => ['template', 'template_name', 'structure_version_id', 'version_number'],
                'set_aside',
                'brought_back',
                'reminders_stopped',
                'reminders_restarted',
                'hidden_count',
            ],
        ],
    ];

    #[Test]
    public function it_previews_a_change_without_making_it(): void
    {
        $user = User::factory()->create();
        $friend = $this->template($user, 'Friend');
        $coworker = $this->template($user, 'Coworker');
        [$vault, $pam] = $this->contactOn($user, $friend);
        $this->valueOf($pam, $this->fieldOn($friend, 'Favourite artist'), 'Edward Hopper');

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$pam->uuid}/template-preview", [
            'template' => $coworker->uuid,
        ]);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.is_change', true);
        $response->assertJsonPath('data.attributes.to.template_name', 'Coworker');
        $this->assertSame($friend->structure->published_version_id, $pam->refresh()->structure_version_id);
    }

    #[Test]
    public function it_names_a_sensitive_field_only_to_a_key_allowed_to_read_them(): void
    {
        $user = User::factory()->create();
        $friend = $this->template($user, 'Friend');
        $coworker = $this->template($user, 'Coworker');
        [$vault, $pam] = $this->contactOn($user, $friend);
        $allergy = $this->fieldOn($friend, 'Allergy');
        $allergy->update(['is_sensitive' => true]);
        $this->valueOf($pam, $allergy, 'Shellfish');
        $url = "/api/v1/vaults/{$vault->uuid}/contacts/{$pam->uuid}/template-preview?template={$coworker->uuid}";

        $this->withToken($user->createToken('Pam’s script')->plainTextToken)
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.attributes.hidden_count', 1);

        $this->app['auth']->forgetGuards();

        $this->withToken($user->createToken('Pam’s script', ['sensitive-fields'])->plainTextToken)
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.attributes.hidden_count', 0);
    }

    #[Test]
    public function it_refuses_a_template_that_is_turned_off_with_the_reason(): void
    {
        $user = User::factory()->create();
        [$vault, $pam] = $this->contactOn($user, $this->template($user, 'Friend'));
        $retired = $this->template($user, 'Coworker');
        $retired->update(['is_active' => false]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$pam->uuid}/template-preview", ['template' => $retired->uuid])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['template' => 'This template is turned off, so no contact can be put on it.']);
    }

    #[Test]
    public function it_answers_not_found_for_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        [$vault, $jan] = $this->contactOn($stranger, $this->template($stranger, 'Friend'));

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$jan->uuid}/template-preview", [
            'template' => $this->template($user, 'Coworker')->uuid,
        ])->assertNotFound();
    }

    private function template(User $user, string $name): Template
    {
        return Template::factory()->published()->create(['account_id' => $user->account_id, 'name' => $name])->refresh();
    }

    /** @return array{Vault, Contact} */
    private function contactOn(User $user, Template $template): array
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$vault, Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $template->structure->published_version_id,
        ])];
    }

    private function fieldOn(Template $template, string $label): Field
    {
        $version = $template->structure->publishedVersion;
        $version->update(['status' => VersionStatusEnum::Draft]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $template->account_id]);
        $field = Field::factory()->create(['account_id' => $template->account_id, 'section_version_id' => $sectionVersion->id, 'label' => $label]);

        StructureVersionSection::factory()->create([
            'account_id' => $template->account_id,
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $version->pages->first()->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->update(['status' => VersionStatusEnum::Published]);
        $version->update(['status' => VersionStatusEnum::Published]);

        return $field;
    }

    private function valueOf(Contact $contact, Field $field, string $text): FieldValue
    {
        return FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => $text,
        ]);
    }
}
