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

/**
 * Deleting an account takes its passkeys with it, and no code in this
 * application does that: `webauthn_keys.user_id` cascades from `users.id`, which
 * cascades from `accounts.id`, and DestroyAccount hard deletes the account.
 *
 * It is a test rather than a comment because the whole chain is database
 * behaviour, and the index on the credential column is the one thing in this
 * feature that differs by driver.
 */
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

    #[Test]
    public function deleting_an_account_leaves_another_accounts_passkeys_alone(): void
    {
        Queue::fake();

        $going = User::factory()->create();
        Passkey::factory()->create(['user_id' => $going->id]);

        $staying = User::factory()->create();
        $kept = Passkey::factory()->create(['user_id' => $staying->id]);

        new DestroyAccount(user: $going, account: $going->account)->execute();

        $this->assertModelExists($kept);
        $this->assertDatabaseCount('passkeys', 1);
    }
}
