<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ChangeContactTemplate;
use App\Enums\FieldTypeEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangeContactTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_contact_on_the_published_version_of_another_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $basic = $this->templateOf($user, 'Basic contact');
        $coworker = $this->templateOf($user, 'Coworker');
        $desk = $this->fieldOn($basic, 'desk', FieldTypeEnum::Text);
        $nickname = $this->fieldOn($basic, 'nickname', FieldTypeEnum::Text);
        $nicknameOnCoworker = $this->fieldOn($coworker, 'nickname', FieldTypeEnum::Text);
        $dwight = $this->contactOn($user, $basic);
        $deskValue = $this->valueOf($dwight, $desk, ['value_string' => 'Next to Jim']);
        $nicknameValue = $this->valueOf($dwight, $nickname, ['value_string' => 'D-Money']);

        $changed = new ChangeContactTemplate(user: $user, contact: $dwight, template: $coworker)->execute();

        $this->assertInstanceOf(Contact::class, $changed);
        $this->assertEquals($coworker->structure->published_version_id, $changed->structure_version_id);
        $this->assertDatabaseHas('field_values', ['id' => $nicknameValue->id, 'field_id' => $nicknameOnCoworker->id, 'is_detached' => false]);
        $this->assertDatabaseHas('field_values', ['id' => $deskValue->id, 'field_id' => $desk->id, 'is_detached' => true]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RecordMovedToTemplate
                && $job->parameters === ['name' => $dwight->name, 'template' => 'Coworker'],
        );
    }

    #[Test]
    public function it_puts_a_contact_on_an_older_published_version_when_named(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $basic = $this->templateOf($user, 'Basic contact');
        $coworker = $this->templateOf($user, 'Coworker');
        $older = StructureVersion::factory()->published()->create(['structure_id' => $coworker->structure->id, 'version_number' => 2]);
        $dwight = $this->contactOn($user, $basic);

        $changed = new ChangeContactTemplate(user: $user, contact: $dwight, template: $coworker, structureVersion: $older)->execute();

        $this->assertEquals($older->id, $changed->structure_version_id);
    }

    #[Test]
    public function it_brings_everything_back_when_the_contact_returns_to_its_first_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $basic = $this->templateOf($user, 'Basic contact');
        $coworker = $this->templateOf($user, 'Coworker');
        $birthday = $this->fieldOn($basic, 'birthday', FieldTypeEnum::RecurringDate);
        $dwight = $this->contactOn($user, $basic);
        $value = $this->valueOf($dwight, $birthday, ['value_date' => '1970-01-20']);
        $reminder = Reminder::factory()->for($value, 'fieldValue')->create();

        new ChangeContactTemplate(user: $user, contact: $dwight, template: $coworker)->execute();

        $this->assertDatabaseHas('field_values', ['id' => $value->id, 'is_detached' => true]);
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_active' => false, 'inactive_reason' => ReminderInactiveReasonEnum::FieldRemoved->value]);

        new ChangeContactTemplate(user: $user, contact: $dwight, template: $basic)->execute();

        $this->assertDatabaseHas('field_values', ['id' => $value->id, 'field_id' => $birthday->id, 'is_detached' => false]);
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_active' => true, 'inactive_reason' => null]);
    }

    #[Test]
    public function it_refuses_a_template_describing_another_kind_of_record(): void
    {
        $user = User::factory()->create();
        $dwight = $this->contactOn($user, $this->templateOf($user, 'Basic contact'));
        $animal = Template::factory()->forEntity()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This template describes another kind of record.');

        new ChangeContactTemplate(user: $user, contact: $dwight, template: $animal)->execute();
    }

    #[Test]
    public function it_refuses_a_template_that_is_turned_off(): void
    {
        $user = User::factory()->create();
        $dwight = $this->contactOn($user, $this->templateOf($user, 'Basic contact'));
        $retired = Template::factory()->inactive()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This template is turned off, so no contact can be put on it.');

        new ChangeContactTemplate(user: $user, contact: $dwight, template: $retired)->execute();
    }

    #[Test]
    public function it_refuses_a_template_with_no_published_version(): void
    {
        $user = User::factory()->create();
        $dwight = $this->contactOn($user, $this->templateOf($user, 'Basic contact'));
        $unfinished = Template::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This template has no published version yet.');

        new ChangeContactTemplate(user: $user, contact: $dwight, template: $unfinished)->execute();
    }

    #[Test]
    public function it_refuses_a_version_of_another_template(): void
    {
        $user = User::factory()->create();
        $basic = $this->templateOf($user, 'Basic contact');
        $coworker = $this->templateOf($user, 'Coworker');
        $dwight = $this->contactOn($user, $basic);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('That version of the template is not published.');

        new ChangeContactTemplate(user: $user, contact: $dwight, template: $coworker, structureVersion: $basic->structure->publishedVersion)->execute();
    }

    #[Test]
    public function it_refuses_a_draft_version(): void
    {
        $user = User::factory()->create();
        $basic = $this->templateOf($user, 'Basic contact');
        $coworker = $this->templateOf($user, 'Coworker');
        $draft = StructureVersion::factory()->create(['structure_id' => $coworker->structure->id, 'version_number' => 2, 'status' => VersionStatusEnum::Draft]);
        $dwight = $this->contactOn($user, $basic);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('That version of the template is not published.');

        new ChangeContactTemplate(user: $user, contact: $dwight, template: $coworker, structureVersion: $draft)->execute();
    }

    #[Test]
    public function it_refuses_a_template_of_another_account(): void
    {
        $user = User::factory()->create();
        $dwight = $this->contactOn($user, $this->templateOf($user, 'Basic contact'));
        $elsewhere = $this->templateOf(User::factory()->create(), 'Coworker');

        $this->expectException(ModelNotFoundException::class);

        new ChangeContactTemplate(user: $user, contact: $dwight, template: $elsewhere)->execute();
    }

    #[Test]
    public function it_refuses_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $jan = $this->contactOn($stranger, $this->templateOf($stranger, 'Basic contact'));

        $this->expectException(ModelNotFoundException::class);

        new ChangeContactTemplate(user: $user, contact: $jan, template: $this->templateOf($user, 'Coworker'))->execute();
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

    private function fieldOn(Template $template, string $key, FieldTypeEnum $type): Field
    {
        $version = $template->structure->publishedVersion;
        $version->update(['status' => VersionStatusEnum::Draft]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $template->account_id]);
        $field = Field::factory()->create([
            'account_id' => $template->account_id,
            'section_version_id' => $sectionVersion->id,
            'key' => $key,
            'type' => $type,
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
