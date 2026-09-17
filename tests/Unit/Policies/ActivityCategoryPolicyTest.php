<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityCategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_deleting_a_category_the_user_made_that_holds_nothing(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $category));
    }

    #[Test]
    public function it_refuses_deleting_a_category_the_product_supplied(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->system()->create(['account_id' => $user->account_id]);

        $response = Gate::forUser($user)->inspect('delete', $category);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('came with your account and stays', (string) $response->message());
    }

    #[Test]
    public function it_refuses_deleting_a_category_still_holding_a_type(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id]);

        $response = Gate::forUser($user)->inspect('delete', $category);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('Types are filed under this category', (string) $response->message());
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $this->assertFalse(Gate::forUser(null)->allows('delete', $category));
    }
}
