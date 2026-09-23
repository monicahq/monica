<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEventCategory;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateLifeEventCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $category = new CreateLifeEventCategory(user: $user, name: 'Volunteering')->execute();

        $this->assertInstanceOf(LifeEventCategory::class, $category);
        $this->assertDatabaseHas('life_event_categories', [
            'account_id' => $user->account_id,
            'name' => 'Volunteering',
            'key' => null,
            'name_translation_key' => null,
            'is_system' => false,
            'is_active' => true,
            'position' => 1,
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::LifeEventCategoryCreation
                && $job->parameters === ['name' => 'Volunteering'];
        });
    }

    #[Test]
    public function it_places_the_category_last(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 4]);

        $category = new CreateLifeEventCategory(user: $user, name: 'Volunteering')->execute();

        $this->assertSame(5, $category->position);
    }

    #[Test]
    public function it_reduces_the_name_to_plain_text(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $category = new CreateLifeEventCategory(user: $user, name: '<b>Volunteering</b>')->execute();

        $this->assertEquals('Volunteering', $category->name);
    }
}
