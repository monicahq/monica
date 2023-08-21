<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Jetstream\Features;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateApiTokenTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function api_tokens_can_be_created()
    {
        if (! Features::hasApiFeatures()) {
            return $this->markTestSkipped('API support is not enabled.');
        }

        $this->actingAs($user = User::factory()->create());

        $response = $this->post('/user/api-tokens', [
            'name' => 'Test Token',
            'permissions' => [
                'read',
                'update',
            ],
        ]);

        $this->assertCount(1, $user->fresh()->tokens);
        $this->assertEquals('Test Token', $user->fresh()->tokens->first()->name);
        $this->assertTrue($user->fresh()->tokens->first()->can('read'));
        $this->assertFalse($user->fresh()->tokens->first()->can('delete'));
    }
}
