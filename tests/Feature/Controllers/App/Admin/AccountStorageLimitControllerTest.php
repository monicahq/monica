<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountStorageLimitControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_a_limit(): void
    {
        $todd = User::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->put(route('admin.accounts.storageLimit.update', ['account' => $todd->account_id]), [
                'storage_limit_in_bytes' => 2048,
            ]);

        $response->assertRedirect(route('admin.accounts.show', ['account' => $todd->account_id]));
        $response->assertSessionHas('status');

        $this->assertEquals(2048, $todd->account->refresh()->storage_limit_in_bytes);
    }

    #[Test]
    public function an_empty_limit_means_no_limit(): void
    {
        $todd = User::factory()->create();
        $todd->account->update(['storage_limit_in_bytes' => 2048]);

        $this->actingAs($this->administrator())
            ->put(route('admin.accounts.storageLimit.update', ['account' => $todd->account_id]), [
                'storage_limit_in_bytes' => '',
            ]);

        $this->assertNull($todd->account->refresh()->storage_limit_in_bytes);
    }

    #[Test]
    public function it_refuses_a_limit_that_is_not_a_number_of_bytes(): void
    {
        $todd = User::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->put(route('admin.accounts.storageLimit.update', ['account' => $todd->account_id]), [
                'storage_limit_in_bytes' => -5,
            ]);

        $response->assertSessionHasErrors('storage_limit_in_bytes');
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
