<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\VaultColourEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionTag;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionTagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Paperwork filed',
            'colour' => VaultColourEnum::Rust->value,
        ]);

        MonicaServer::actingAs($user)->tool(UpdatePossessionTag::class, [
            'possession_tag' => $possessionTag->uuid,
            'name' => 'Paperwork done',
        ])->assertOk()->assertHasNoErrors()->assertSee('Paperwork done');

        $this->assertDatabaseHas('possession_tags', [
            'id' => $possessionTag->id,
            'name' => 'Paperwork done',
            'colour' => VaultColourEnum::Rust->value,
        ]);
    }

    #[Test]
    public function it_reports_a_tag_of_another_account_as_not_existing(): void
    {
        $possessionTag = PossessionTag::factory()->create(['name' => 'Fragile']);

        MonicaServer::actingAs(User::factory()->create())->tool(UpdatePossessionTag::class, [
            'possession_tag' => $possessionTag->uuid,
            'name' => 'Very fragile',
        ])->assertHasErrors();

        $this->assertSame('Fragile', $possessionTag->refresh()->name);
    }
}
