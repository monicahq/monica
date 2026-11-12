<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListColumnPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_removing_a_column_when_another_stays(): void
    {
        $user = User::factory()->create();
        $list = $this->listFor($user);
        $column = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $column));
    }

    #[Test]
    public function it_refuses_removing_the_last_column_of_a_list(): void
    {
        $user = User::factory()->create();
        $column = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $this->listFor($user)->id]);

        $response = Gate::forUser($user)->inspect('delete', $column);

        $this->assertTrue($response->denied());
        $this->assertEquals('The last column cannot be removed. A list needs at least one.', $response->message());
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $list = KnowledgeList::factory()->create();
        $column = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);

        $this->assertFalse(Gate::forUser(null)->allows('delete', $column));
    }

    private function listFor(User $user): KnowledgeList
    {
        return KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id,
        ]);
    }
}
