<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionTags;

use App\Enums\VaultColourEnum;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTagControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_possession_tags_of_the_account(): void
    {
        $user = User::factory()->create();
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);

        $this->actingAs($user)->get(route('settings.possessionTags.index'))
            ->assertStatus(200)
            ->assertSee('Insured')
            ->assertSee('aria-label="Edit Insured"', false)
            ->assertSee('name="colour"', false);
    }

    #[Test]
    public function it_adds_a_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.possessionTags.create'), ['name' => 'Insured', 'colour' => VaultColourEnum::Teal->value])
            ->assertRedirect(route('settings.possessionTags.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('possession_tags', [
            'account_id' => $user->account_id,
            'name' => 'Insured',
            'colour' => VaultColourEnum::Teal->value,
        ]);
    }

    #[Test]
    public function it_refuses_a_name_longer_than_fifty_characters_and_a_colour_outside_the_palette(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('settings.possessionTags.create'), ['name' => str_repeat('a', 51), 'colour' => '#000000'])
            ->assertSessionHasErrorsIn('newPossessionTag', ['name', 'colour']);
    }

    #[Test]
    public function it_returns_the_duplicate_refusal_to_the_add_form(): void
    {
        $user = User::factory()->create();
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);

        $this->actingAs($user)
            ->post(route('settings.possessionTags.create'), ['name' => 'insured', 'colour' => VaultColourEnum::Blue->value])
            ->assertSessionHasErrorsIn('newPossessionTag', ['name']);
    }

    #[Test]
    public function it_renames_and_recolours_a_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Paperwork filed']);

        $this->actingAs($user)
            ->put(route('settings.possessionTags.update', $possessionTag), ['name' => 'Paperwork done', 'colour' => VaultColourEnum::Plum->value])
            ->assertRedirect(route('settings.possessionTags.index'))
            ->assertSessionHas('status');

        $possessionTag->refresh();

        $this->assertSame('Paperwork done', $possessionTag->name);
        $this->assertSame(VaultColourEnum::Plum->value, $possessionTag->colour);
    }

    #[Test]
    public function it_returns_a_refused_rename_to_the_form_of_its_row(): void
    {
        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('settings.possessionTags.update', $possessionTag), ['name' => '', 'colour' => 'red'])
            ->assertSessionHasErrorsIn("possessionTag{$possessionTag->uuid}", ['name', 'colour']);
    }

    #[Test]
    public function it_deletes_a_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Needs repair']);

        $this->actingAs($user)
            ->delete(route('settings.possessionTags.destroy', $possessionTag))
            ->assertRedirect(route('settings.possessionTags.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($possessionTag);
    }
}
