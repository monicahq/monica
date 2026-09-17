<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\ActivityTypes;

use App\Actions\CreateActivityType;
use App\Actions\ReplaceActivityTypeFields;
use App\Enums\FieldTypeEnum;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use App\ViewModels\Settings\Account\ActivityTypes\ActivityTypeFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityTypeFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_when_it_holds_no_type(): void
    {
        $user = User::factory()->create();

        $viewModel = new ActivityTypeFormViewModel(user: $user);

        $this->assertFalse($viewModel->isEdit());
        $this->assertSame(__('Add an activity type'), $viewModel->title());
        $this->assertSame('post', $viewModel->method());
        $this->assertSame(route('settings.activityTypes.create'), $viewModel->action());
    }

    #[Test]
    public function it_edits_when_it_holds_a_type(): void
    {
        $user = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $user->account_id, 'name' => 'Coffee']);

        $viewModel = new ActivityTypeFormViewModel(user: $user, activityType: $type);

        $this->assertTrue($viewModel->isEdit());
        $this->assertSame('Coffee', $viewModel->title());
        $this->assertSame('put', $viewModel->method());
        $this->assertSame(route('settings.activityTypes.update', $type), $viewModel->action());
    }

    #[Test]
    public function it_offers_a_category_that_is_switched_off_and_says_so(): void
    {
        $user = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Leisure', 'position' => 1]);
        ActivityCategory::factory()->inactive()->create(['account_id' => $user->account_id, 'name' => 'Retired', 'position' => 2]);

        $labels = array_column(new ActivityTypeFormViewModel(user: $user)->categories(), 'label');

        $this->assertSame(['Leisure', __(':name (switched off)', ['name' => 'Retired'])], $labels);
    }

    #[Test]
    public function it_starts_on_the_category_somebody_came_from(): void
    {
        $user = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $wanted = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        $viewModel = new ActivityTypeFormViewModel(user: $user, wantedCategory: $wanted);

        $this->assertSame($wanted->id, $viewModel->categoryId());
        $this->assertSame(route('settings.activityTypes.category', $wanted), $viewModel->backUrl());
    }

    #[Test]
    public function it_refuses_to_delete_a_type_the_product_supplied_and_says_why(): void
    {
        $user = User::factory()->create();
        $type = ActivityType::factory()->supplied()->create(['account_id' => $user->account_id]);

        $viewModel = new ActivityTypeFormViewModel(user: $user, activityType: $type);

        $this->assertFalse($viewModel->canDelete());
        $this->assertStringContainsString('came with your account', (string) $viewModel->deleteRefusal());
    }

    #[Test]
    public function it_allows_deleting_a_type_the_user_made(): void
    {
        $user = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new ActivityTypeFormViewModel(user: $user, activityType: $type);

        $this->assertTrue($viewModel->canDelete());
        $this->assertNull($viewModel->deleteRefusal());
    }

    #[Test]
    public function it_always_ends_with_the_block_for_the_fields_in_no_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = new CreateActivityType(
            user: $user,
            activityCategory: ActivityCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Medical appointment',
        )->execute();

        $blocks = new ActivityTypeFormViewModel(user: $user, activityType: $type)->blocks();

        $this->assertCount(1, $blocks);
        $this->assertFalse($blocks[0]['isGroup']);
        $this->assertSame('', $blocks[0]['reference']);
        $this->assertSame([], $blocks[0]['fields']);
    }

    #[Test]
    public function it_reads_the_groups_and_the_loose_fields_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = new CreateActivityType(
            user: $user,
            activityCategory: ActivityCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Medical appointment',
        )->execute();

        new ReplaceActivityTypeFields(
            user: $user,
            activityType: $type,
            groups: [['reference' => 'g1', 'placement_id' => null, 'name' => 'The appointment']],
            fields: [
                ['key' => null, 'group' => 'g1', 'label' => 'Who you saw', 'type' => 'text', 'is_required' => true, 'help_text' => null, 'settings' => ['placeholder' => 'Dr Meyer'], 'choices' => []],
                ['key' => null, 'group' => '', 'label' => 'Anything you were given', 'type' => 'file', 'is_required' => false, 'help_text' => null, 'settings' => [], 'choices' => []],
            ],
        )->execute();

        $blocks = new ActivityTypeFormViewModel(user: $user, activityType: $type->refresh())->blocks();

        $this->assertCount(2, $blocks);
        $this->assertTrue($blocks[0]['isGroup']);
        $this->assertSame('The appointment', $blocks[0]['name']);
        $this->assertSame('Who you saw', $blocks[0]['fields'][0]['label']);
        $this->assertTrue($blocks[0]['fields'][0]['isRequired']);
        $this->assertSame('Dr Meyer', $blocks[0]['fields'][0]['settings']['placeholder']);

        $this->assertFalse($blocks[1]['isGroup']);
        $this->assertSame('Anything you were given', $blocks[1]['fields'][0]['label']);
        $this->assertSame('', $blocks[1]['fields'][0]['group']);
    }

    #[Test]
    public function it_offers_only_the_kinds_of_field_that_can_be_written(): void
    {
        $user = User::factory()->create();

        $offered = collect(new ActivityTypeFormViewModel(user: $user)->fieldTypeFamilies())
            ->flatMap(fn (array $family): array => array_column($family['types'], 'key'))
            ->all();

        $this->assertContains('text', $offered);
        $this->assertNotContains('rating', $offered);
        $this->assertSame(count(FieldTypeEnum::cases()), count($offered));
    }

    #[Test]
    public function it_says_what_switching_the_type_off_changes(): void
    {
        $user = User::factory()->create();
        $offered = ActivityType::factory()->create(['account_id' => $user->account_id]);
        $retired = ActivityType::factory()->inactive()->create(['account_id' => $user->account_id]);

        $this->assertStringContainsString(
            'keep their type',
            new ActivityTypeFormViewModel(user: $user, activityType: $offered)->availabilityHelp(),
        );

        $this->assertStringContainsString(
            'not offered for new activities',
            new ActivityTypeFormViewModel(user: $user, activityType: $retired)->availabilityHelp(),
        );
    }
}
