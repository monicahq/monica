<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiTokenPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function api_token_permissions_can_be_updated()
    {
        if (! Features::hasApiFeatures()) {
            return $this->markTestSkipped('API support is not enabled.');
        }

        $this->actingAs($user = User::factory()->create());

        $token = $user->tokens()->create([
            'name' => 'Test Token',
            'token' => Str::random(40),
            'abilities' => ['read'],
        ]);

        $response = $this->put('/user/api-tokens/'.$token->id, [
            'name' => $token->name,
            'permissions' => [
                'write',
                'missing-permission',
            ],
        ]);

        $this->assertTrue($user->fresh()->tokens->first()->can('write'));
        $this->assertFalse($user->fresh()->tokens->first()->can('read'));
        $this->assertFalse($user->fresh()->tokens->first()->can('missing-permission'));
    }
}
