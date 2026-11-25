<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Actions\ChangeContactTemplate;
use App\Enums\FieldTypeEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\TemplateChangePreview;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\SectionVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateChangePreviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_what_leaves_what_comes_back_and_the_reminders_on_them(): void
    {
        $user = User::factory()->create();
        $basic = $this->templateOf($user, 'Basic contact');
        $coworker = $this->templateOf($user, 'Coworker');
        $birthday = $this->fieldOn($basic, 'birthday', FieldTypeEnum::RecurringDate, 'Birthday');
        $desk = $this->fieldOn($coworker, 'desk', FieldTypeEnum::Text, 'Desk');
        $dwight = $this->contactOn($user, $basic);
        $born = $this->valueOf($dwight, $birthday, ['value_date' => '1970-01-20']);
        $birthdayReminder = Reminder::factory()->for($born, 'fieldValue')->create(['name' => 'Dwight’s birthday']);
        $seat = $this->valueOf($dwight, $desk, ['value_string' => 'Next to Jim', 'is_detached' => true, 'detached_at' => now()]);
        $deskReminder = Reminder::factory()->for($seat, 'fieldValue')->create([
            'name' => 'Check the desk',
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::FieldRemoved,
            'deactivated_at' => now(),
        ]);

        $preview = new TemplateChangePreview(
            contact: $dwight,
            target: $coworker->structure->publishedVersion,
            namesSensitiveFields: true,
        )->toArray();

        $this->assertTrue($preview['isChange']);
        $this->assertSame([['field_id' => $birthday->id, 'label' => 'Birthday', 'section' => $birthday->sectionVersion->section->name, 'count' => 1]], $preview['setAside']);
        $this->assertSame([['field_id' => $desk->id, 'label' => 'Desk', 'section' => $desk->sectionVersion->section->name, 'count' => 1]], $preview['broughtBack']);
        $this->assertSame([['id' => $birthdayReminder->uuid, 'label' => 'Dwight’s birthday']], $preview['remindersStopped']);
        $this->assertSame([['id' => $deskReminder->uuid, 'label' => 'Check the desk']], $preview['remindersRestarted']);
        $this->assertSame(0, $preview['hiddenCount']);
        $this->assertDatabaseHas('field_values', ['id' => $born->id, 'is_detached' => false]);
        $this->assertDatabaseHas('reminders', ['id' => $birthdayReminder->id, 'is_active' => true]);
    }

    #[Test]
    public function it_says_nothing_changes_on_the_version_the_contact_already_uses(): void
    {
        $user = User::factory()->create();
        $basic = $this->templateOf($user, 'Basic contact');
        $dwight = $this->contactOn($user, $basic);

        $preview = new TemplateChangePreview(
            contact: $dwight,
            target: $basic->structure->publishedVersion,
            namesSensitiveFields: true,
        )->toArray();

        $this->assertFalse($preview['isChange']);
        $this->assertSame([], $preview['setAside']);
    }

    #[Test]
    public function it_counts_a_sensitive_answer_without_naming_it_unless_allowed(): void
    {
        $user = User::factory()->create();
        $basic = $this->templateOf($user, 'Basic contact');
        $coworker = $this->templateOf($user, 'Coworker');
        $condition = $this->fieldOn($basic, 'allergy', FieldTypeEnum::Text, 'Allergy');
        $condition->update(['is_sensitive' => true]);
        $dwight = $this->contactOn($user, $basic);
        $this->valueOf($dwight, $condition, ['value_string' => 'Bees']);
        $target = $coworker->structure->publishedVersion;

        $hidden = new TemplateChangePreview(contact: $dwight, target: $target, namesSensitiveFields: false)->toArray();
        $named = new TemplateChangePreview(contact: $dwight, target: $target, namesSensitiveFields: true)->toArray();

        $this->assertSame([], $hidden['setAside']);
        $this->assertSame(1, $hidden['hiddenCount']);
        $this->assertSame('Allergy', $named['setAside'][0]['label']);
        $this->assertSame(0, $named['hiddenCount']);
    }

    #[Test]
    public function it_lists_exactly_what_the_change_then_sets_aside(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $basic = $this->templateOf($user, 'Basic contact');
        $coworker = $this->templateOf($user, 'Coworker');
        $nickname = $this->fieldOn($basic, 'nickname', FieldTypeEnum::Text, 'Nickname');
        $desk = $this->fieldOn($basic, 'desk', FieldTypeEnum::Text, 'Desk');
        $this->fieldOn($coworker, 'nickname', FieldTypeEnum::Text, 'Nickname');
        $dwight = $this->contactOn($user, $basic);
        $this->valueOf($dwight, $nickname, ['value_string' => 'D-Money']);
        $this->valueOf($dwight, $desk, ['value_string' => 'Next to Jim']);

        $preview = new TemplateChangePreview(
            contact: $dwight,
            target: $coworker->structure->publishedVersion,
            namesSensitiveFields: true,
        )->toArray();

        new ChangeContactTemplate(user: $user, contact: $dwight, template: $coworker)->execute();

        $detached = FieldValue::query()->where('valuable_id', $dwight->id)->where('is_detached', true)->pluck('field_id')->all();

        $this->assertSame([$desk->id], array_column($preview['setAside'], 'field_id'));
        $this->assertSame($detached, array_column($preview['setAside'], 'field_id'));
    }

    private function templateOf(User $user, string $name): Template
    {
        return Template::factory()->published()->create(['account_id' => $user->account_id, 'name' => $name]);
    }

    private function contactOn(User $user, Template $template): Contact
    {
        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'structure_version_id' => $template->structure->published_version_id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);
    }

    private function fieldOn(Template $template, string $key, FieldTypeEnum $type, string $label): Field
    {
        $version = $template->structure->publishedVersion;
        $version->update(['status' => VersionStatusEnum::Draft]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $template->account_id]);
        $field = Field::factory()->create([
            'account_id' => $template->account_id,
            'section_version_id' => $sectionVersion->id,
            'key' => $key,
            'type' => $type,
            'label' => $label,
        ]);

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

    /** @param  array<string, mixed>  $attributes */
    private function valueOf(Contact $contact, Field $field, array $attributes): FieldValue
    {
        return FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => null,
            ...$attributes,
        ]);
    }
}
