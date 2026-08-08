<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\EntityTypes;

use App\Models\EntityType;
use App\Models\User;
use App\ViewModels\Settings\Account\EntityTypes\EntityTypesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTypesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_types_of_the_account_in_navigation_order(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_singular' => 'Company',
            'position' => 2,
        ]);
        EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_singular' => 'Animal',
            'position' => 1,
        ]);

        $types = new EntityTypesViewModel(user: $user)->types();

        $this->assertCount(2, $types);
        $this->assertSame('Animal', $types[0]['nameSingular']);
        $this->assertSame('Company', $types[1]['nameSingular']);
    }

    #[Test]
    public function it_leaves_out_the_types_of_another_account(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        EntityType::factory()->create(['account_id' => $dwight->account_id]);

        $this->assertCount(0, new EntityTypesViewModel(user: $michael)->types());
    }

    #[Test]
    public function it_gives_a_row_everything_it_is_drawn_from(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->used(4)->create([
            'account_id' => $user->account_id,
            'slug' => 'animals',
            'icon' => 'device',
            'colour' => '#4f8a49',
        ]);

        $row = new EntityTypesViewModel(user: $user)->types()[0];

        $this->assertSame($entityType->id, $row['id']);
        $this->assertSame('animals', $row['slug']);
        $this->assertSame('device', $row['icon']);
        $this->assertSame('#4f8a49', $row['colour']);
        $this->assertSame('color-mix(in srgb, #4f8a49 12%, transparent)', $row['tint']);
        $this->assertTrue($row['isActive']);
        $this->assertSame('4 records', $row['countLabel']);
    }

    #[Test]
    public function it_says_how_much_a_type_holds(): void
    {
        $this->assertSame('no records', EntityTypesViewModel::countLabel(0));
        $this->assertSame('1 record', EntityTypesViewModel::countLabel(1));
        $this->assertSame('9 records', EntityTypesViewModel::countLabel(9));
    }

    #[Test]
    public function it_sums_the_records_of_every_type_for_the_heading(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->used(4)->create(['account_id' => $user->account_id]);
        EntityType::factory()->used(7)->create(['account_id' => $user->account_id]);

        $this->assertSame(
            '2 types · 11 records across all vaults',
            new EntityTypesViewModel(user: $user)->summary(),
        );
    }

    #[Test]
    public function it_says_so_when_the_account_has_one_of_each(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->used(1)->create(['account_id' => $user->account_id]);

        $this->assertSame(
            '1 type · 1 record across all vaults',
            new EntityTypesViewModel(user: $user)->summary(),
        );
    }

    #[Test]
    public function it_gives_the_user_to_the_sidebar(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->is(new EntityTypesViewModel(user: $user)->user()));
    }
}
