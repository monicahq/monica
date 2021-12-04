<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VaultTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $vault = Vault::factory()->create();
        $this->assertTrue($vault->account()->exists());
    }

    /** @test */
    public function it_has_many_users(): void
    {
        $dwight = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $dwight->account_id,
        ]);

        $vault->users()->sync([$dwight->id => ['permission' => Vault::PERMISSION_MANAGE]]);

        $this->assertTrue($vault->users()->exists());
    }
}
