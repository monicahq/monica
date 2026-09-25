<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateField;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\AcceptedFileKindIsNotValid;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_mark_a_field_as_identifying(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $field = Field::factory()->create(['account_id' => $user->account_id, 'label' => 'Email']);

        new UpdateField(
            user: $user,
            field: $field,
            label: 'Email',
            isIdentifying: true,
        )->execute();

        $this->assertTrue($field->refresh()->is_identifying);
    }

    #[Test]
    public function it_can_stop_a_field_identifying_anybody(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $field = Field::factory()->create([
            'account_id' => $user->account_id,
            'label' => 'Email',
            'is_identifying' => true,
        ]);

        new UpdateField(user: $user, field: $field, label: 'Email')->execute();

        $this->assertFalse($field->refresh()->is_identifying);
    }

    #[Test]
    public function it_changes_what_a_field_asks_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        $field = Field::factory()->create(['section_version_id' => $version->id, 'label' => 'Street']);

        new UpdateField(
            user: $user,
            field: $field,
            label: 'Street address',
            helpText: 'Number and street',
            placeholder: '1725 Slough Avenue',
            isRequired: true,
            isSensitive: true,
        )->execute();

        $field->refresh();

        $this->assertEquals('Street address', $field->label);
        $this->assertEquals('Number and street', $field->help_text);
        $this->assertEquals('1725 Slough Avenue', $field->placeholder);
        $this->assertTrue($field->is_required);
        $this->assertTrue($field->is_sensitive);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::FieldUpdate
                && $job->parameters === ['label' => 'Street address', 'name' => 'Address'];
        });
    }

    #[Test]
    public function it_leaves_the_key_alone_when_the_label_changes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $field = Field::factory()->create(['account_id' => $user->account_id, 'key' => 'shoe_size']);

        new UpdateField(user: $user, field: $field, label: 'Size of shoe')->execute();

        $this->assertEquals('shoe_size', $field->fresh()->key);
    }

    #[Test]
    public function it_leaves_the_type_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $field = Field::factory()->recurringDate()->create(['account_id' => $user->account_id]);

        new UpdateField(user: $user, field: $field, label: 'Anniversary')->execute();

        $this->assertEquals($field->type, $field->fresh()->type);
    }

    #[Test]
    public function it_swaps_the_list_a_choice_field_reads_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $field = Field::factory()->choice()->create(['account_id' => $user->account_id]);
        $other = ValueList::factory()->create(['account_id' => $user->account_id]);

        new UpdateField(user: $user, field: $field, label: 'Gender', valueList: $other)->execute();

        $this->assertEquals($other->id, $field->fresh()->value_list_id);
    }

    #[Test]
    public function it_swaps_the_list_a_phone_takes_its_labels_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $other = ValueList::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create([
            'account_id' => $user->account_id,
            'type' => FieldTypeEnum::Phone,
            'value_list_id' => $list->id,
        ]);

        new UpdateField(user: $user, field: $field, label: 'Phone', valueList: $other)->execute();

        $this->assertEquals($other->id, $field->fresh()->value_list_id);
    }

    #[Test]
    public function it_lets_a_phone_be_left_with_no_labels_to_offer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create([
            'account_id' => $user->account_id,
            'type' => FieldTypeEnum::Phone,
            'value_list_id' => $list->id,
        ]);

        new UpdateField(user: $user, field: $field, label: 'Phone')->execute();

        $this->assertNull($field->fresh()->value_list_id);
    }

    #[Test]
    public function it_swaps_the_list_an_address_takes_its_labels_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $other = ValueList::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create([
            'account_id' => $user->account_id,
            'type' => FieldTypeEnum::Address,
            'value_list_id' => $list->id,
        ]);

        new UpdateField(user: $user, field: $field, label: 'Address', valueList: $other)->execute();

        $this->assertEquals($other->id, $field->fresh()->value_list_id);
    }

    #[Test]
    public function it_refuses_to_leave_a_choice_field_with_nothing_to_offer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $field = Field::factory()->choice()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new UpdateField(user: $user, field: $field, label: 'Gender')->execute();
    }

    #[Test]
    public function it_refuses_to_change_a_field_on_a_published_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create(['section_version_id' => $version->id]);
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new UpdateField(user: $user, field: $field->fresh(), label: 'Something else')->execute();
    }

    #[Test]
    public function it_saves_the_kinds_of_file_a_field_takes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $field = Field::factory()->create(['account_id' => $user->account_id, 'type' => FieldTypeEnum::File, 'label' => 'Contract']);

        new UpdateField(
            user: $user,
            field: $field,
            label: 'Contract',
            options: ['accepted_mime_types' => ['application/pdf', 'image/png']],
        )->execute();

        $this->assertEquals(['accepted_mime_types' => ['application/pdf', 'image/png']], $field->refresh()->options);
    }

    #[Test]
    public function it_refuses_a_kind_of_file_that_is_not_a_mime_type(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->create(['account_id' => $user->account_id, 'type' => FieldTypeEnum::File]);

        $this->expectException(AcceptedFileKindIsNotValid::class);

        new UpdateField(user: $user, field: $field, label: 'Contract', options: ['accepted_mime_types' => ['pdf']])->execute();
    }

    #[Test]
    public function it_refuses_more_kinds_of_file_than_a_field_may_take(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->create(['account_id' => $user->account_id, 'type' => FieldTypeEnum::File]);
        $kinds = array_map(fn (int $number): string => "application/x-dunder-{$number}", range(1, Field::MAX_ACCEPTED_MIME_TYPES + 1));

        $this->expectException(AcceptedFileKindIsNotValid::class);

        new UpdateField(user: $user, field: $field, label: 'Contract', options: ['accepted_mime_types' => $kinds])->execute();
    }

    #[Test]
    public function it_refuses_an_image_field_taking_something_that_is_not_an_image(): void
    {
        $user = User::factory()->create();
        $field = Field::factory()->create(['account_id' => $user->account_id, 'type' => FieldTypeEnum::Image]);

        $this->expectException(AcceptedFileKindIsNotValid::class);

        new UpdateField(user: $user, field: $field, label: 'Photo', options: ['accepted_mime_types' => ['image/png', 'application/pdf']])->execute();
    }

    #[Test]
    public function it_refuses_a_field_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $field = Field::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateField(user: $michael, field: $field, label: 'Street')->execute();
    }
}
