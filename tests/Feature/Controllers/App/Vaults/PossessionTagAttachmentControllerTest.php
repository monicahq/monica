<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\VaultColourEnum;
use App\Models\Possession;
use App\Models\PossessionTag;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTagAttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_tag_on_a_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $fragile = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Fragile']);

        $this->actingAs($user)
            ->from(route('possessions.show', [$vault, $dundie]))
            ->put(route('possessions.tags.update', [$vault, $dundie, $fragile]))
            ->assertRedirect(route('possessions.show', [$vault, $dundie]))
            ->assertSessionHas('status', 'Added “Fragile”.');

        $this->assertTrue($dundie->tags()->whereKey($fragile->id)->exists());
    }

    #[Test]
    public function it_creates_a_tag_in_the_next_colour_and_puts_it_on_the_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'colour' => VaultColourEnum::Blue->value]);

        $this->actingAs($user)
            ->post(route('possessions.tags.create', [$vault, $dundie]), ['name' => 'Swiss made'])
            ->assertSessionHas('status', 'Created “Swiss made” and added it.');

        $this->assertDatabaseHas('possession_tags', [
            'account_id' => $user->account_id,
            'name' => 'Swiss made',
            'colour' => VaultColourEnum::Green->value,
        ]);
        $this->assertSame(['Swiss made'], $dundie->tags()->pluck('name')->all());
    }

    #[Test]
    public function it_refuses_a_name_that_is_empty_too_long_or_already_used(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Fragile']);

        $this->actingAs($user)->post(route('possessions.tags.create', [$vault, $dundie]), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->actingAs($user)->post(route('possessions.tags.create', [$vault, $dundie]), ['name' => str_repeat('a', 51)])
            ->assertSessionHasErrors('name');

        $this->actingAs($user)->post(route('possessions.tags.create', [$vault, $dundie]), ['name' => 'fragile'])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, $dundie->tags()->count());
    }

    #[Test]
    public function it_takes_a_tag_off_a_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $shredder = Possession::factory()->create(['vault_id' => $vault->id]);
        $toSell = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'To sell']);
        $shredder->tags()->attach($toSell->id);

        $this->actingAs($user)
            ->delete(route('possessions.tags.destroy', [$vault, $shredder, $toSell]))
            ->assertSessionHas('status', 'Removed “To sell” from this possession.');

        $this->assertSame(0, $shredder->tags()->count());
        $this->assertModelExists($toSell);
    }

    #[Test]
    public function it_answers_not_found_for_a_possession_reached_through_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $otherVault->id]);
        $fragile = PossessionTag::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('possessions.tags.update', [$vault, $dundie, $fragile]))
            ->assertNotFound();
    }
}
