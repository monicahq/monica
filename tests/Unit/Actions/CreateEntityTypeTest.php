<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateEntityType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateEntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_declares_a_kind_of_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $entityType = new CreateEntityType(
            user: $user,
            nameSingular: 'Wine bottle',
            namePlural: 'Wine bottles',
            description: 'What is in the cellar',
        )->execute();

        $this->assertInstanceOf(EntityType::class, $entityType);

        $this->assertDatabaseHas('entity_types', [
            'id' => $entityType->id,
            'account_id' => $user->account_id,
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
            'slug' => 'wine-bottle',
            'description' => 'What is in the cellar',
            'position' => 1,
            'is_active' => true,
            'entity_count' => 0,
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job) use ($user): bool {
            return $job->action === UserActionEnum::EntityTypeCreation
                && $job->parameters === ['name' => 'Wine bottles']
                && $job->user->is($user);
        });
    }

    #[Test]
    public function it_gives_a_type_the_default_colour_when_nobody_picked_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $entityType = new CreateEntityType(
            user: $user,
            nameSingular: 'Animal',
            namePlural: 'Animals',
        )->execute();

        $this->assertEquals(EntityType::DEFAULT_COLOUR, $entityType->colour);
    }

    #[Test]
    public function it_puts_a_new_type_at_the_end_of_the_navigation(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 4]);

        $entityType = new CreateEntityType(
            user: $user,
            nameSingular: 'Animal',
            namePlural: 'Animals',
        )->execute();

        $this->assertSame(5, $entityType->position);
    }

    #[Test]
    public function it_takes_the_slug_it_is_given_rather_than_one_made_from_the_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $entityType = new CreateEntityType(
            user: $user,
            nameSingular: 'Wine bottle',
            namePlural: 'Wine bottles',
            slug: 'cellar',
        )->execute();

        $this->assertEquals('cellar', $entityType->slug);
    }

    #[Test]
    public function it_counts_up_when_the_slug_it_wants_is_taken(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $first = new CreateEntityType(user: $user, nameSingular: 'Animal', namePlural: 'Animals')->execute();
        $second = new CreateEntityType(user: $user, nameSingular: 'Animal', namePlural: 'Animals')->execute();
        $third = new CreateEntityType(user: $user, nameSingular: 'Animal', namePlural: 'Animals')->execute();

        $this->assertEquals('animal', $first->slug);
        $this->assertEquals('animal-2', $second->slug);
        $this->assertEquals('animal-3', $third->slug);
    }

    #[Test]
    public function it_leaves_the_slug_of_another_account_alone(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();

        new CreateEntityType(user: $michael, nameSingular: 'Animal', namePlural: 'Animals')->execute();
        $beets = new CreateEntityType(user: $dwight, nameSingular: 'Animal', namePlural: 'Animals')->execute();

        $this->assertEquals('animal', $beets->slug);
    }

    #[Test]
    public function it_does_not_hand_out_the_slug_of_a_deleted_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'animal'])->delete();

        $entityType = new CreateEntityType(user: $user, nameSingular: 'Animal', namePlural: 'Animals')->execute();

        $this->assertEquals('animal-2', $entityType->slug);
    }

    #[Test]
    public function it_strips_html_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $entityType = new CreateEntityType(
            user: $user,
            nameSingular: '<b>Animal</b>',
            namePlural: '<script>alert(1)</script>Animals',
        )->execute();

        $this->assertEquals('Animal', $entityType->name_singular);
        $this->assertEquals('Animals', $entityType->name_plural);
    }
}
