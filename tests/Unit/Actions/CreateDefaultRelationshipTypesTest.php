<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultRelationshipTypes;
use App\Enums\RelationshipBehaviorEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultRelationshipTypesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_account_the_five_categories_in_order(): void
    {
        $user = $this->michael();

        new CreateDefaultRelationshipTypes(user: $user)->execute();

        $categories = RelationshipCategory::query()
            ->where('account_id', $user->account_id)
            ->orderBy('position')
            ->get();

        $this->assertEquals(
            ['family', 'romantic', 'friendship', 'professional', 'other'],
            $categories->pluck('key')->all(),
        );
        $this->assertEquals([1, 2, 3, 4, 5], $categories->pluck('position')->all());
        $this->assertTrue($categories->every(fn (RelationshipCategory $category): bool => $category->is_system));
    }

    #[Test]
    public function it_gives_the_account_the_ten_supplied_types(): void
    {
        $user = $this->michael();

        $types = new CreateDefaultRelationshipTypes(user: $user)->execute();

        $this->assertCount(10, $types);
        $this->assertEquals(10, RelationshipType::query()->where('account_id', $user->account_id)->count());
        $this->assertDatabaseHas('relationship_types', [
            'account_id' => $user->account_id,
            'key' => 'parent_child',
            'name' => null,
            'name_translation_key' => 'Parent / Child',
            'role_a_translation_key' => 'Parent',
            'role_b_translation_key' => 'Child',
            'is_symmetric' => false,
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_files_the_types_under_their_categories_in_order(): void
    {
        $user = $this->michael();

        new CreateDefaultRelationshipTypes(user: $user)->execute();

        $family = RelationshipCategory::query()
            ->where('account_id', $user->account_id)
            ->where('key', 'family')
            ->firstOrFail();

        $this->assertEquals(
            ['parent_child', 'sibling', 'grandparent_grandchild', 'aunt_uncle_niece_nephew', 'cousin'],
            $family->types->pluck('key')->all(),
        );
        $this->assertEquals([1, 2, 3, 4, 5], $family->types->pluck('position')->all());
    }

    #[Test]
    public function other_is_supplied_empty(): void
    {
        $user = $this->michael();

        new CreateDefaultRelationshipTypes(user: $user)->execute();

        $other = RelationshipCategory::query()
            ->where('account_id', $user->account_id)
            ->where('key', 'other')
            ->firstOrFail();

        $this->assertCount(0, $other->types);
    }

    #[Test]
    public function only_four_types_carry_a_protected_behaviour(): void
    {
        $user = $this->michael();

        new CreateDefaultRelationshipTypes(user: $user)->execute();

        $types = RelationshipType::query()->where('account_id', $user->account_id)->orderBy('id')->get();

        $this->assertEquals(
            ['parent_child'],
            $types->where('system_behavior', RelationshipBehaviorEnum::GenealogyParentage)->pluck('key')->values()->all(),
        );
        $this->assertEquals(
            ['partner', 'fiance', 'spouse'],
            $types->where('system_behavior', RelationshipBehaviorEnum::Romantic)->pluck('key')->values()->all(),
        );
    }

    #[Test]
    public function it_writes_the_seeding_to_the_journal(): void
    {
        Queue::fake();
        $user = $this->michael();

        new CreateDefaultRelationshipTypes(user: $user)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipTypesSeeded);
    }

    private function michael(): User
    {
        $account = Account::factory()->create();

        return $this->assignUserToAccount(user: User::factory()->make(), account: $account);
    }
}
