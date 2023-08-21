<?php

namespace Tests\Feature\Controllers\Profile;

use App\Models\User;
use App\Models\UserToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTokenControllerTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_deletes_token(): void
    {
        $user = User::factory()->create();
        $userToken = UserToken::factory([
            'user_id' => $user->id,
        ])->create();

        $this->actingAs($user)->delete('/auth/github');

        $this->assertNull($userToken->fresh());
    }
}
