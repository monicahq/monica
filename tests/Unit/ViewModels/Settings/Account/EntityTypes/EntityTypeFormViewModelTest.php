<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\EntityTypes;

use App\Models\EntityType;
use App\Models\User;
use App\ViewModels\Settings\Account\EntityTypes\EntityTypeFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTypeFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_new_type_on_the_defaults(): void
    {
        $user = User::factory()->create();

        $viewModel = new EntityTypeFormViewModel(user: $user);

        $this->assertFalse($viewModel->isEdit());
        $this->assertSame('post', $viewModel->method());
        $this->assertNull($viewModel->slug());

        $values = $viewModel->values();

        $this->assertSame('', $values['nameSingular']);
        $this->assertSame(EntityType::DEFAULT_ICON, $values['icon']);
        $this->assertSame(EntityType::DEFAULT_COLOUR, $values['colour']);
        $this->assertTrue($values['isActive']);
    }

    #[Test]
    public function it_fills_the_form_from_a_type_that_exists(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
            'slug' => 'animals',
            'icon' => 'device',
            'colour' => '#4f8a49',
        ]);

        $viewModel = new EntityTypeFormViewModel(user: $user, entityType: $entityType);

        $this->assertTrue($viewModel->isEdit());
        $this->assertSame('put', $viewModel->method());
        $this->assertSame('animals', $viewModel->slug());
        $this->assertSame($entityType->uuid, $viewModel->uuid());

        $values = $viewModel->values();

        $this->assertSame('Animals', $values['namePlural']);
        $this->assertSame('device', $values['icon']);
        $this->assertFalse($values['isActive']);
    }

    #[Test]
    public function it_marks_the_icon_and_the_colour_in_use(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'icon' => 'device',
            'colour' => '#9a3f2b',
        ]);

        $viewModel = new EntityTypeFormViewModel(user: $user, entityType: $entityType);

        $icons = collect($viewModel->icons());
        $colours = collect($viewModel->colours());

        $this->assertCount(count(EntityType::ICONS), $icons);
        $this->assertCount(count(EntityType::COLOURS), $colours);
        $this->assertSame('device', $icons->firstWhere('selected', true)['name']);
        $this->assertSame('#9a3f2b', $colours->firstWhere('selected', true)['value']);
    }

    #[Test]
    public function it_says_how_much_the_type_holds(): void
    {
        $user = User::factory()->create();

        $this->assertNull(new EntityTypeFormViewModel(user: $user)->recordCount());

        EntityType::factory()->used(1)->create(['account_id' => $user->account_id]);
        EntityType::factory()->used(6)->create(['account_id' => $user->account_id]);

    }

    #[Test]
    public function it_offers_a_deletion_only_while_the_type_holds_nothing(): void
    {
        $user = User::factory()->create();
        $empty = EntityType::factory()->create(['account_id' => $user->account_id]);
        $used = EntityType::factory()->used()->create(['account_id' => $user->account_id]);

        $this->assertFalse(new EntityTypeFormViewModel(user: $user)->canBeDeleted());
        $this->assertTrue(new EntityTypeFormViewModel(user: $user, entityType: $empty)->canBeDeleted());
        $this->assertFalse(new EntityTypeFormViewModel(user: $user, entityType: $used)->canBeDeleted());
    }
}
