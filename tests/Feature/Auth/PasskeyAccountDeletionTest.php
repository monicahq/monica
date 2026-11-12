<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Actions\DestroyAccount;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function deleting_an_account_leaves_no_passkey_behind(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);

        new DestroyAccount(user: $user, account: $user->account)->execute();

        $this->assertModelMissing($passkey);
        $this->assertDatabaseCount('passkeys', 0);
    }
}
