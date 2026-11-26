<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultTrashControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_what_waits_in_the_trash(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Toby', 'last_name' => 'Flenderson', 'deleted_at' => now()]);

        $this->actingAs($user)->get(route('vaults.trash.index', ['vault' => $vault]))
            ->assertOk()
            ->assertSee('Toby Flenderson')
            ->assertSee(route('vaults.trash.restoration.update', ['vault' => $vault, 'kind' => 'contact', 'item' => $toby->uuid]))
            ->assertSee(route('vaults.trash.empty', ['vault' => $vault]));
    }

    #[Test]
    public function it_answers_not_found_for_a_kind_behind_a_flag_that_is_off(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Feature::for($user->account)->deactivate('groups');
        Feature::flushCache();

        $this->actingAs($user)->get(route('vaults.trash.index', ['vault' => $vault, 'kind' => 'group']))->assertNotFound();
    }

    #[Test]
    public function it_empties_the_trash(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);

        $this->actingAs($user)->delete(route('vaults.trash.empty', ['vault' => $vault]))
            ->assertRedirect(route('vaults.trash.index', ['vault' => $vault]))
            ->assertSessionHas('status', 'The trash is empty.');

        $this->assertModelMissing($toby);
    }

    #[Test]
    public function it_restores_an_item_and_says_so(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Toby', 'last_name' => 'Flenderson', 'deleted_at' => now()]);

        $this->actingAs($user)->put(route('vaults.trash.restoration.update', ['vault' => $vault, 'kind' => 'contact', 'item' => $toby->uuid]))
            ->assertRedirect(route('vaults.trash.index', ['vault' => $vault]))
            ->assertSessionHas('status', 'Toby Flenderson is back.');

        $this->assertNotSoftDeleted($toby);
    }

    #[Test]
    public function it_deletes_an_item_for_good(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);

        $this->actingAs($user)->delete(route('vaults.trash.destroy', ['vault' => $vault, 'kind' => 'contact', 'item' => $toby->uuid]))
            ->assertRedirect(route('vaults.trash.index', ['vault' => $vault]))
            ->assertSessionHas('status', 'Deleted for good.');

        $this->assertModelMissing($toby);
    }

    #[Test]
    public function it_leaves_an_item_of_another_vault_where_it_is(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'deleted_at' => now()]);

        $this->actingAs($user)
            ->put(route('vaults.trash.restoration.update', ['vault' => $vault, 'kind' => 'contact', 'item' => $toby->uuid]))
            ->assertRedirect(route('vaults.trash.index', ['vault' => $vault]))
            ->assertSessionHas('error', 'This item is no longer in the trash. It was restored or deleted for good in the meantime.');

        $this->assertSoftDeleted($toby);
    }

    #[Test]
    public function it_says_an_item_deleted_for_good_meanwhile_is_no_longer_in_the_trash(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);
        $toby->forceDelete();

        $this->actingAs($user)
            ->delete(route('vaults.trash.destroy', ['vault' => $vault, 'kind' => 'contact', 'item' => $toby->uuid]))
            ->assertRedirect(route('vaults.trash.index', ['vault' => $vault]))
            ->assertSessionHas('error', 'This item is no longer in the trash. It was restored or deleted for good in the meantime.');
    }
}
