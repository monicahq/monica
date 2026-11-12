<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ImportPossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportPossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_possession_type_from_a_file(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = json_encode([
            'format' => 'possession_type',
            'format_version' => 1,
            'possession_type' => [
                'name' => 'Office equipment',
                'groups' => [['name' => 'Purchase', 'fields' => [['name' => 'Shop', 'kind' => 'text']]]],
                'fields' => [['name' => 'Serial number', 'kind' => 'text']],
            ],
        ], JSON_THROW_ON_ERROR);

        MonicaServer::actingAs($user)->tool(ImportPossessionType::class, ['file' => $file])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Office equipment');

        $this->assertDatabaseHas('possession_types', ['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $this->assertDatabaseHas('possession_field_groups', ['account_id' => $user->account_id, 'name' => 'Purchase']);
        $this->assertDatabaseHas('possession_fields', ['account_id' => $user->account_id, 'name' => 'Shop']);
        $this->assertDatabaseHas('possession_fields', ['account_id' => $user->account_id, 'name' => 'Serial number']);
    }

    #[Test]
    public function it_lists_every_problem_of_a_file_it_cannot_read(): void
    {
        $file = json_encode([
            'format' => 'possession_type',
            'format_version' => 1,
            'possession_type' => [
                'name' => '',
                'fields' => [['name' => '', 'kind' => 'hologram']],
            ],
        ], JSON_THROW_ON_ERROR);

        MonicaServer::actingAs(User::factory()->create())->tool(ImportPossessionType::class, ['file' => $file])
            ->assertHasErrors();

        $this->assertDatabaseCount('possession_types', 0);
    }
}
