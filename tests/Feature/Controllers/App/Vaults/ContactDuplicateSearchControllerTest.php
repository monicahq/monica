<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\AccountImportStatusEnum;
use App\Jobs\DetectDuplicateContacts;
use App\Models\AccountImport;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDuplicateSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_search_for_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->post(route('vaults.duplicates.search.create', $vault));

        $response->assertRedirect(route('vaults.duplicates.index', $vault));
        $response->assertSessionHas('status');
        Queue::assertPushedOn('low', DetectDuplicateContacts::class);
    }

    #[Test]
    public function it_says_the_search_waits_while_an_import_writes_into_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        AccountImport::factory()->create(['user_id' => $user->id, 'status' => AccountImportStatusEnum::Writing, 'vault_id' => $vault->id]);

        $response = $this->actingAs($user)->post(route('vaults.duplicates.search.create', $vault));

        $response->assertRedirect(route('vaults.duplicates.index', $vault));
        $response->assertSessionHas('status', 'The search waits for the import.');
        Queue::assertNothingPushed();
    }
}
