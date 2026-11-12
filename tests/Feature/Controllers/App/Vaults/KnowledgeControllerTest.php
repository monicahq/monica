<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_contents_and_finds_what_is_searched(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Dundies speech', 'body' => "## Best dressed\nPam"]);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Fire drill', 'body' => 'Dwight']);

        $this->actingAs($user)->get(route('knowledge.index', $vault))
            ->assertOk()
            ->assertSee('Contents')
            ->assertSee('Best dressed')
            ->assertSee('Fire drill');

        $this->actingAs($user)->get(route('knowledge.index', ['vault' => $vault, 'search' => 'dwight']))
            ->assertOk()
            ->assertSee('Fire drill');

        $this->actingAs($user)->get(route('knowledge.index', ['vault' => $vault, 'search' => 'forklift']))
            ->assertOk()
            ->assertSee('Nothing matches “forklift” in titles or text.');
    }
}
