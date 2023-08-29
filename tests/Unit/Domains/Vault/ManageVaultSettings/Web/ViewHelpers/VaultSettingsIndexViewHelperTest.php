<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Web\ViewHelpers;

use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Models\Contact;
use App\Models\Label;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\MoodTrackingParameter;
use App\Models\Tag;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultQuickFactsTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class VaultSettingsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_information_necessary_to_load_the_view(): void
    {
        $template = Template::factory()->create();
        $userInAccount = User::factory()->create([
            'account_id' => $template->account_id,
        ]);
        $userInVault = User::factory()->create([
            'account_id' => $template->account_id,
        ]);
        $vault = Vault::factory()->create([
            'account_id' => $template->account_id,
        ]);
        $vault->users()->sync([$userInVault->id => ['permission' => 100, 'contact_id' => Contact::factory()->create()->id]]);
        MoodTrackingParameter::factory()->create([
            'vault_id' => $vault->id,
            'label' => 'test',
            'position' => 2,
        ]);

        $vault->refresh();
        $array = VaultSettingsIndexViewHelper::data($vault);
        $this->assertCount(
            13,
            $array
        );
        $this->assertArrayHasKey('templates', $array);
        $this->assertArrayHasKey('users_in_vault', $array);
        $this->assertArrayHasKey('users_in_account', $array);
        $this->assertArrayHasKey('url', $array);
        $this->assertArrayHasKey('labels', $array);
        $this->assertArrayHasKey('label_colors', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertArrayHasKey('mood_tracking_parameters', $array);
        $this->assertArrayHasKey('mood_tracking_parameter_colors', $array);
        $this->assertArrayHasKey('life_event_categories', $array);
        $this->assertArrayHasKey('quick_fact_templates', $array);
        $this->assertEquals(
            [
                0 => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'is_default' => false,
                ],
            ],
            $array['templates']->toArray()
        );
        $this->assertEquals(
            [
                'show_group_tab' => true,
                'show_tasks_tab' => true,
                'show_files_tab' => true,
                'show_journal_tab' => true,
                'show_companies_tab' => true,
                'show_reports_tab' => true,
                'show_calendar_tab' => true,
            ],
            $array['visibility']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $userInVault->id,
                    'name' => $userInVault->name,
                    'permission' => 100,
                    'url' => [
                        'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/users/'.$userInVault->id,
                        'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/settings/users/'.$userInVault->id,
                    ],
                ],
            ],
            $array['users_in_vault']->toArray()
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $userInAccount->id,
                    'name' => $userInAccount->name,
                    'permission' => null,
                    'url' => [
                        'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/users/'.$userInAccount->id,
                        'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/settings/users/'.$userInAccount->id,
                    ],
                ],
            ],
            $array['users_in_account']->toArray()
        );
        $this->assertEquals(
            [
                'template_update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/template',
                'user_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/users',
                'label_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/labels',
                'tag_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/tags',
                'contact_date_important_date_type_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/contactImportantDateTypes',
                'mood_tracking_parameter_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/moodTrackingParameters',
                'life_event_category_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/lifeEventCategories',
                'quick_fact_templates_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/quickFactTemplates',
                'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings',
                'update_tab_visibility' => env('APP_URL').'/vaults/'.$vault->id.'/settings/visibility',
                'destroy' => env('APP_URL').'/vaults/'.$vault->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $vault = Vault::factory()->create();
        $label = Label::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $array = VaultSettingsIndexViewHelper::dtoLabel($label);
        $this->assertEquals(
            [
                'id' => $label->id,
                'name' => $label->name,
                'count' => null,
                'bg_color' => $label->bg_color,
                'text_color' => $label->text_color,
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/labels/'.$label->id,
                    'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/settings/labels/'.$label->id,
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object_tag(): void
    {
        $vault = Vault::factory()->create();
        $tag = Tag::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $array = VaultSettingsIndexViewHelper::dtoTag($tag);
        $this->assertEquals(
            [
                'id' => $tag->id,
                'name' => $tag->name,
                'count' => null,
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/tags/'.$tag->id,
                    'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/settings/tags/'.$tag->id,
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_mood_tracking_parameter(): void
    {
        $vault = Vault::factory()->create();
        $moodTrackingParameter = MoodTrackingParameter::factory()->create([
            'vault_id' => $vault->id,
            'label' => 'test',
            'hex_color' => '3243',
            'position' => 2,
        ]);
        $array = VaultSettingsIndexViewHelper::dtoMoodTrackingParameter($moodTrackingParameter);
        $this->assertEquals(
            [
                'id' => $moodTrackingParameter->id,
                'label' => 'test',
                'hex_color' => '3243',
                'position' => 2,
                'url' => [
                    'position' => env('APP_URL').'/vaults/'.$vault->id.'/settings/moodTrackingParameters/'.$moodTrackingParameter->id.'/order',
                    'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/moodTrackingParameters/'.$moodTrackingParameter->id,
                    'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/settings/moodTrackingParameters/'.$moodTrackingParameter->id,
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_dto_for_life_event_category(): void
    {
        $lifeEventCategory = LifeEventCategory::factory()->create();

        $array = VaultSettingsIndexViewHelper::dtoLifeEventCategory($lifeEventCategory);
        $this->assertEquals(
            6,
            count($array)
        );
        $this->assertArrayHasKey('life_event_types', $array);
        $this->assertEquals(
            $lifeEventCategory->label,
            $array['label']
        );
        $this->assertEquals(
            true,
            $array['can_be_deleted']
        );
        $this->assertEquals(
            1,
            $array['position']
        );
        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$lifeEventCategory->vault_id.'/settings/lifeEventCategories/'.$lifeEventCategory->id.'/lifeEventTypes',
                'position' => env('APP_URL').'/vaults/'.$lifeEventCategory->vault_id.'/settings/lifeEventCategories/'.$lifeEventCategory->id.'/order',
                'update' => env('APP_URL').'/vaults/'.$lifeEventCategory->vault_id.'/settings/lifeEventCategories/'.$lifeEventCategory->id,
                'destroy' => env('APP_URL').'/vaults/'.$lifeEventCategory->vault_id.'/settings/lifeEventCategories/'.$lifeEventCategory->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_dto_for_life_event_type(): void
    {
        $lifeEventCategory = LifeEventCategory::factory()->create();
        $lifeEventType = LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
        ]);

        $array = VaultSettingsIndexViewHelper::dtoType($lifeEventCategory, $lifeEventType);
        $this->assertEquals(
            6,
            count($array)
        );
        $this->assertEquals(
            [
                'id' => $lifeEventType->id,
                'label' => $lifeEventType->label,
                'can_be_deleted' => $lifeEventType->can_be_deleted,
                'life_event_category_id' => $lifeEventCategory->id,
                'position' => 1,
                'url' => [
                    'position' => env('APP_URL').'/vaults/'.$lifeEventCategory->vault_id.'/settings/lifeEventCategories/'.$lifeEventCategory->id.'/lifeEventTypes/'.$lifeEventType->id.'/order',
                    'update' => env('APP_URL').'/vaults/'.$lifeEventCategory->vault_id.'/settings/lifeEventCategories/'.$lifeEventCategory->id.'/lifeEventTypes/'.$lifeEventType->id,
                    'destroy' => env('APP_URL').'/vaults/'.$lifeEventCategory->vault_id.'/settings/lifeEventCategories/'.$lifeEventCategory->id.'/lifeEventTypes/'.$lifeEventType->id,
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_dto_for_quick_fact_template(): void
    {
        $template = VaultQuickFactsTemplate::factory()->create();

        $array = VaultSettingsIndexViewHelper::dtoQuickFactTemplateEntry($template);
        $this->assertEquals(
            4,
            count($array)
        );
        $this->assertEquals(
            [
                'id' => $template->id,
                'label' => $template->label,
                'position' => 1,
                'url' => [
                    'position' => env('APP_URL').'/vaults/'.$template->vault_id.'/settings/quickFactTemplates/'.$template->id.'/order',
                    'update' => env('APP_URL').'/vaults/'.$template->vault_id.'/settings/quickFactTemplates/'.$template->id,
                    'destroy' => env('APP_URL').'/vaults/'.$template->vault_id.'/settings/quickFactTemplates/'.$template->id,
                ],
            ],
            $array
        );
    }
}
