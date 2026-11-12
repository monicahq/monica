<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\ValueList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_deleting_a_list_the_account_wrote(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $list));
    }

    #[Test]
    public function it_refuses_deleting_a_list_the_product_supplied(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'is_system' => true]);

        $response = Gate::forUser($user)->inspect('delete', $list);

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_allows_adding_a_value_to_an_open_list(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'allows_user_values' => true]);

        $this->assertTrue(Gate::forUser($user)->allows('addItem', $list));
    }

    #[Test]
    public function it_refuses_adding_a_value_to_a_closed_list(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id, 'allows_user_values' => false]);

        $response = Gate::forUser($user)->inspect('addItem', $list);

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $user = User::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $this->assertFalse(Gate::forUser(null)->allows('delete', $list));
        $this->assertFalse(Gate::forUser(null)->allows('addItem', $list));
    }
}
