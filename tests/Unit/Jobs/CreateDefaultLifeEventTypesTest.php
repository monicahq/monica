<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateDefaultLifeEventCategories;
use App\Enums\LifeEventCategoryEnum;
use App\Enums\LifeEventTypeEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\CreateDefaultLifeEventTypes;
use App\Jobs\LogUserAction;
use App\Models\LifeEventType;
use App\Models\Field;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultLifeEventTypesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_account_every_nature_a_life_event_can_be_recorded_as(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        $this->assertCount(40, LifeEventType::query()->where('account_id', $user->account_id)->get());

        $this->assertDatabaseHas('life_event_types', [
            'account_id' => $user->account_id,
            'key' => 'medical_appointment',
            'name' => null,
            'name_translation_key' => 'Medical appointment',
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_files_each_type_under_the_category_it_belongs_to(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        foreach (LifeEventTypeEnum::cases() as $case) {
            $type = LifeEventType::query()->where('key', $case->value)->sole();

            $this->assertSame($case->category()->value, $type->lifeEventCategory->key);
        }
    }

    #[Test]
    public function it_numbers_the_types_from_one_inside_each_category(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        foreach (LifeEventCategoryEnum::cases() as $case) {
            $positions = LifeEventType::query()
                ->whereRelation('lifeEventCategory', 'key', $case->value)
                ->orderBy('position')
                ->pluck('position')
                ->all();

            $this->assertEquals(range(1, count($positions)), $positions);
        }
    }

    #[Test]
    public function it_publishes_the_structure_a_type_asks_its_fields_through(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        foreach (LifeEventType::query()->get() as $type) {
            $this->assertSame(StructureAppliesToEnum::LifeEvent, $type->structure->applies_to);
            $this->assertNotNull($type->structure->published_version_id);

            $version = $type->structure->publishedVersion;

            $this->assertSame(VersionStatusEnum::Published, $version->status);
            $this->assertSame(1, $version->version_number);
            $this->assertCount(1, $version->pages);
        }
    }

    #[Test]
    public function it_writes_the_fields_each_type_asks_for(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        foreach (LifeEventTypeEnum::cases() as $case) {
            $assembly = LifeEventType::query()->where('key', $case->value)->sole()->structure->publishedVersion->assembly;

            $this->assertCount(count($case->blocks()), $assembly);

            foreach ($case->blocks() as $index => $block) {
                $row = $assembly->values()[$index];

                $this->assertSame($block['name'] !== null, $row->shows_title);
                $this->assertSame($block['name'] ?? 'Fields in no group', $row->sectionVersion->section->name);
                $this->assertEquals(
                    array_column($block['fields'], 'key'),
                    $row->sectionVersion->fields()->orderBy('position')->pluck('key')->all(),
                );
            }
        }
    }

    #[Test]
    public function it_reads_the_fields_in_no_group_before_any_group(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        $assembly = LifeEventType::query()->where('key', 'move')->sole()->structure->publishedVersion->assembly;

        $this->assertFalse($assembly->values()[0]->shows_title);
        $this->assertEquals(['Addresses', 'Documents'], $assembly->skip(1)->map(fn ($row): string => $row->sectionVersion->section->name)->values()->all());
    }

    #[Test]
    public function it_keeps_the_choices_of_a_field_in_a_list_of_that_field_alone(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        $channel = $this->fieldOf('call', 'channel');

        $this->assertSame(FieldTypeEnum::Select, $channel->type);
        $this->assertFalse($channel->valueList->is_shared);
        $this->assertNull($channel->valueList->key);
        $this->assertSame('Channel', $channel->valueList->name);
        $this->assertEquals(
            ['Phone', 'Video', 'Voice call in an app'],
            $channel->valueList->items()->orderBy('position')->get()->map(fn ($item): string => $item->label)->all(),
        );
    }

    #[Test]
    public function it_offers_no_supplied_list_of_choices_in_the_account_vocabulary(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        $this->assertSame(
            0,
            ValueList::query()->where('account_id', $user->account_id)->where('is_shared', true)->count(),
        );
    }

    #[Test]
    public function it_carries_the_settings_a_field_needs_to_be_read_as_a_measurement(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        $distance = $this->fieldOf('hike', 'distance')->options;
        $rating = $this->fieldOf('restaurant', 'rating')->options;

        $this->assertCount(2, $distance);
        $this->assertSame('km', $distance['unit']);
        $this->assertSame(0, $distance['min']);

        $this->assertCount(3, $rating);
        $this->assertSame(1, $rating['min']);
        $this->assertSame(5, $rating['max']);
        $this->assertSame(0, $rating['decimals']);
    }

    #[Test]
    public function it_names_what_it_supplies_through_a_translation_key_so_it_reads_in_the_user_language(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        $type = LifeEventType::query()->where('key', 'restaurant')->sole();
        $field = $this->fieldOf('restaurant', 'dish');

        $this->assertNull($type->getRawOriginal('name'));
        $this->assertSame('Restaurant', $type->name);
        $this->assertNull($field->getRawOriginal('label'));
        $this->assertSame('Dish or menu', $field->label);
    }

    #[Test]
    public function it_marks_no_supplied_field_sensitive_since_no_screen_can_take_the_mark_off(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        $this->assertSame(
            0,
            Field::query()->where('account_id', $user->account_id)->where('is_sensitive', true)->count(),
        );
    }

    #[Test]
    public function it_asks_for_none_of_them_when_the_account_has_no_category_to_file_them_under(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        $this->assertSame(0, LifeEventType::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_logs_that_the_types_the_account_starts_with_were_set_up(): void
    {
        Queue::fake();

        $user = $this->userWithLifeEventCategories();

        new CreateDefaultLifeEventTypes(user: $user)->handle();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LifeEventTypesSeeded,
        );
    }

    private function fieldOf(string $type, string $key): Field
    {
        $version = LifeEventType::query()->where('key', $type)->sole()->structure->publishedVersion;

        return Field::query()
            ->whereIn('section_version_id', $version->assembly->pluck('section_version_id'))
            ->where('key', $key)
            ->sole();
    }

    private function userWithLifeEventCategories(): User
    {
        $user = User::factory()->create();

        new CreateDefaultLifeEventCategories(user: $user)->execute();

        return $user;
    }
}
