<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\KnowledgeFolder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeFolderPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_deleting_a_folder_when_another_stays(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $folder));
    }

    #[Test]
    public function it_refuses_deleting_the_last_folder_of_the_vault(): void
    {
        $user = User::factory()->create();
        $folder = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        KnowledgeFolder::factory()->create();

        $response = Gate::forUser($user)->inspect('delete', $folder);

        $this->assertTrue($response->denied());
        $this->assertEquals('The last folder cannot be deleted. Knowledge needs at least one.', $response->message());
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $vault = Vault::factory()->create();
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        $this->assertFalse(Gate::forUser(null)->allows('delete', $folder));
    }
}
