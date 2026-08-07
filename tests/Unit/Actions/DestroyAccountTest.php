<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyAccount;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_destroys_the_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        new DestroyAccount(
            user: $user,
            account: $account,
        )->execute();

        $this->assertModelMissing($account);
    }

    #[Test]
    public function it_throws_when_the_user_belongs_to_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $account = Account::factory()->create();
        $stranger = User::factory()->create();

        new DestroyAccount(
            user: $stranger,
            account: $account,
        )->execute();
    }
}
