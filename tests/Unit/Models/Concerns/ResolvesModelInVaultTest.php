<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResolvesModelInVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_a_record_of_the_account_when_the_url_names_no_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'original_name' => 'dundie-awards.pdf',
        ]);

        $this->actingAs($user);

        $this->assertTrue($file->is(new File()->resolveRouteBinding($file->uuid)));
    }

    #[Test]
    public function it_does_not_find_a_record_of_another_account_when_the_url_names_no_vault(): void
    {
        $user = User::factory()->create();
        $somebodyElse = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $somebodyElse->account_id]);
        $file = File::factory()->create([
            'account_id' => $somebodyElse->account_id,
            'vault_id' => $vault->id,
            'original_name' => 'sabre-handbook.pdf',
        ]);

        $this->actingAs($user);

        $this->assertNull(new File()->resolveRouteBinding($file->uuid));
    }

    #[Test]
    public function it_accepts_the_primary_key_as_a_second_spelling_of_the_address(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        $this->actingAs($user);

        $this->assertTrue($file->is(new File()->resolveRouteBinding((string) $file->id)));
    }

    #[Test]
    public function it_does_not_find_a_record_of_another_account_by_its_primary_key(): void
    {
        $user = User::factory()->create();
        $somebodyElse = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $somebodyElse->account_id]);
        $file = File::factory()->create([
            'account_id' => $somebodyElse->account_id,
            'vault_id' => $vault->id,
        ]);

        $this->actingAs($user);

        $this->assertNull(new File()->resolveRouteBinding((string) $file->id));
    }

    #[Test]
    public function it_finds_nothing_when_nobody_is_signed_in(): void
    {
        $file = File::factory()->create();

        $this->assertNull(new File()->resolveRouteBinding($file->uuid));
    }
}
