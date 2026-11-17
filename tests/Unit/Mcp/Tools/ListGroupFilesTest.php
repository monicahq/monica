<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListGroupFiles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListGroupFilesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_withdrawn_when_file_upload_is_not_offered(): void
    {
        $user = User::factory()->create();
        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        MonicaServer::actingAs($user)->tool(ListGroupFiles::class, ['vault' => 'x', 'group' => 'y'])
            ->assertHasErrors();
    }
}
