<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Knowledge;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_the_notes_and_lists_holding_the_words(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Fire drill', 'body' => 'Dwight lit it']);
        KnowledgeList::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Fire wardens']);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Dundies']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/knowledge/search?query=fire")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'knowledge_note')
            ->assertJsonPath('data.1.type', 'knowledge_list');
    }

    #[Test]
    public function it_refuses_a_search_too_short(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/knowledge/search?query=f")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');
    }
}
