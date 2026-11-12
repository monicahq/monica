<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\LifeEventTypes;

use App\Actions\CreateLifeEventType;
use App\Actions\ReplaceLifeEventTypeFields;
use App\Enums\FieldTypeEnum;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use App\ViewModels\Settings\Account\LifeEventTypes\LifeEventTypeFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTypeFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_when_it_holds_no_type(): void
    {
        $user = User::factory()->create();

        $viewModel = new LifeEventTypeFormViewModel(user: $user);

        $this->assertFalse($viewModel->isEdit());
        $this->assertSame('post', $viewModel->method());
        $this->assertSame(route('settings.lifeEventTypes.create'), $viewModel->action());
    }

    #[Test]
    public function it_edits_when_it_holds_a_type(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'name' => 'Coffee']);

        $viewModel = new LifeEventTypeFormViewModel(user: $user, lifeEventType: $type);

        $this->assertTrue($viewModel->isEdit());
        $this->assertSame('Coffee', $viewModel->title());
        $this->assertSame('put', $viewModel->method());
        $this->assertSame(route('settings.lifeEventTypes.update', $type), $viewModel->action());
    }

    #[Test]
    public function it_offers_a_category_that_is_switched_off_and_says_so(): void
    {
        $user = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Leisure', 'position' => 1]);
        LifeEventCategory::factory()->inactive()->create(['account_id' => $user->account_id, 'name' => 'Retired', 'position' => 2]);

        $labels = array_column(new LifeEventTypeFormViewModel(user: $user)->categories(), 'label');

        $this->assertContains('Leisure', $labels);
    }

    #[Test]
    public function it_starts_on_the_category_somebody_came_from(): void
    {
        $user = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $wanted = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        $viewModel = new LifeEventTypeFormViewModel(user: $user, wantedCategory: $wanted);

        $this->assertSame($wanted->id, $viewModel->categoryId());
        $this->assertSame(route('settings.lifeEventTypes.category', $wanted), $viewModel->backUrl());
    }

    #[Test]
    public function it_refuses_to_delete_a_type_the_product_supplied_and_says_why(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->supplied()->create(['account_id' => $user->account_id]);

        $viewModel = new LifeEventTypeFormViewModel(user: $user, lifeEventType: $type);

        $this->assertFalse($viewModel->canDelete());
    }

    #[Test]
    public function it_allows_deleting_a_type_the_user_made(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new LifeEventTypeFormViewModel(user: $user, lifeEventType: $type);

        $this->assertTrue($viewModel->canDelete());
        $this->assertNull($viewModel->deleteRefusal());
    }

    #[Test]
    public function it_always_ends_with_the_block_for_the_fields_in_no_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = new CreateLifeEventType(
            user: $user,
            lifeEventCategory: LifeEventCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Medical appointment',
        )->execute();

        $blocks = new LifeEventTypeFormViewModel(user: $user, lifeEventType: $type)->blocks();

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
        $type = new CreateLifeEventType(
            user: $user,
            lifeEventCategory: LifeEventCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Medical appointment',
        )->execute();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [['reference' => 'g1', 'placement_id' => null, 'name' => 'The appointment']],
            fields: [
                ['key' => null, 'group' => 'g1', 'label' => 'Who you saw', 'type' => 'text', 'is_required' => true, 'help_text' => null, 'settings' => ['placeholder' => 'Dr Meyer'], 'choices' => []],
                ['key' => null, 'group' => '', 'label' => 'Anything you were given', 'type' => 'file', 'is_required' => false, 'help_text' => null, 'settings' => [], 'choices' => []],
            ],
        )->execute();

        $blocks = new LifeEventTypeFormViewModel(user: $user, lifeEventType: $type->refresh())->blocks();

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

        $offered = collect(new LifeEventTypeFormViewModel(user: $user)->fieldTypeFamilies())
            ->flatMap(fn (array $family): array => array_column($family['types'], 'key'))
            ->all();

        $this->assertContains('text', $offered);
        $this->assertNotContains('rating', $offered);
        $this->assertSame(count(FieldTypeEnum::cases()), count($offered));
    }
}
