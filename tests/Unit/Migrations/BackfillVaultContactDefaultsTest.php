<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Enums\DefaultTemplateEnum;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillVaultContactDefaultsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_a_vault_without_a_contact_default_the_next_supplied_template(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id, 'is_system' => true, 'slug' => DefaultTemplateEnum::FullPortrait->value]);
        $essentials = Template::factory()->published()->create(['account_id' => $user->account_id, 'is_system' => true, 'slug' => DefaultTemplateEnum::Essentials->value]);
        Template::factory()->published()->create(['account_id' => $user->account_id, 'is_system' => true, 'slug' => DefaultTemplateEnum::BasicContact->value])->delete();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->migration()->up();

        $this->assertDatabaseHas('vault_default_templates', [
            'vault_id' => $vault->id,
            'applies_to' => 'contact',
            'structure_version_id' => $essentials->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_leaves_a_vault_that_has_a_contact_default_alone(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $default = VaultDefaultTemplate::factory()->create(['vault_id' => $vault->id]);

        $this->migration()->up();

        $this->assertSame(1, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->count());
        $this->assertModelExists($default);
    }

    #[Test]
    public function it_leaves_a_vault_alone_when_its_account_has_no_contact_template(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->migration()->up();

        $this->assertDatabaseMissing('vault_default_templates', ['vault_id' => $vault->id]);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_20_000000_backfill_vault_contact_defaults.php');
    }
}
