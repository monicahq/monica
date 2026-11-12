<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListItemPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_deleting_a_value_nothing_points_at(): void
    {
        $user = User::factory()->create();
        $item = $this->value($user, 0);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $item));
    }

    #[Test]
    public function it_refuses_deleting_a_value_records_point_at(): void
    {
        $user = User::factory()->create();
        $item = $this->value($user, 4);

        $response = Gate::forUser($user)->inspect('delete', $item);

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $user = User::factory()->create();
        $item = $this->value($user, 0);

        $this->assertFalse(Gate::forUser(null)->allows('delete', $item));
    }

    private function value(User $user, int $usageCount): ValueListItem
    {
        return ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => ValueList::factory()->create(['account_id' => $user->account_id])->id,
            'usage_count' => $usageCount,
        ]);
    }
}
