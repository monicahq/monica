<?php

namespace Tests\Feature\Controllers\Profile;

use App\Models\User;
use App\Models\UserToken;
use Tests\TestCase;

class UserTokenControllerTest extends TestCase
{
    /** @test */
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
