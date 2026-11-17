<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListGroups;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListGroupsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_not_offered_while_groups_are_off(): void
    {
        $user = User::factory()->create();
        $user->account->features()->deactivate('groups');
        Feature::flushCache();

        MonicaServer::actingAs($user)->tools()->assertNotRegistered([ListGroups::class]);
    }
}
