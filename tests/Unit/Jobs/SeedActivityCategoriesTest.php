<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SeedActivityCategories;
use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeedActivityCategoriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_the_action_to_do_the_work(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new SeedActivityCategories(user: $user)->handle();

        $this->assertTrue(ActivityCategory::query()->where('account_id', $user->account_id)->exists());
    }
}
