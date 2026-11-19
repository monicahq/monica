<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DAVRedirectTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_well_known_carddav_and_caldav_using_propfind(): void
    {
        $response = $this->call('PROPFIND', '/.well-known/carddav', server: [
            'HTTP_DEPTH' => '0',
        ]);

        $response->assertStatus(301);
        $response->assertHeader('Location', route('sabre.dav'));
    }

    #[Test]
    public function it_sends_a_browser_back_to_the_home_screen(): void
    {
        $response = $this->davGet();

        $response->assertStatus(302);
        $response->assertHeader('Location', route('home.index'));
    }

    #[Test]
    public function it_lists_the_tree_instead_in_the_local_environment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');

        $response = $this->davGet();

        $response->assertStatus(200);
        $response->assertSee('addressbooks', escape: false);
    }

    private function davGet(): TestResponse
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dwight’s phone')->plainTextToken;

        return $this->call('GET', 'dav/', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode($user->email.':'.$key),
        ]);
    }
}
