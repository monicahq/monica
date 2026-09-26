<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\LogSourceEnum;
use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetLog;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_one_entry_of_the_activity(): void
    {
        $user = User::factory()->create();
        $log = Log::factory()->create([
            'account_id' => $user->account_id,
            'source' => LogSourceEnum::Mcp,
            'ip_address' => '10.0.0.1',
        ]);

        EricaServer::actingAs($user)->tool(GetLog::class, ['log' => (string) $log->id])
            ->assertOk()
            ->assertSee('"source":"mcp"')
            ->assertSee('10.0.0.1');
    }

    #[Test]
    public function it_reports_an_entry_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $log = Log::factory()->create();

        EricaServer::actingAs($user)->tool(GetLog::class, ['log' => (string) $log->id])
            ->assertHasErrors(['That log entry does not exist.']);
    }

    #[Test]
    public function it_reports_an_identifier_that_is_not_a_number_as_not_existing(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(GetLog::class, ['log' => 'abc'])
            ->assertHasErrors(['That log entry does not exist.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.logs.show', app(GetLog::class)->mirrors());
    }
}
