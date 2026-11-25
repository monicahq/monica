<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\TemplateChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class TemplateChangeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_first_step(): void
    {
        $fixture = AnimalsVault::build();

        $this->actingAs($fixture->user)
            ->get(route('entities.templateChanges.new', ['vault' => $fixture->vault, 'entityType' => $fixture->animals, 'from' => $fixture->horse->uuid]))
            ->assertStatus(200)
            ->assertSee('Change template')
            ->assertSee('Preview changes');
    }

    #[Test]
    public function it_shows_the_preview(): void
    {
        $fixture = AnimalsVault::build();

        $this->actingAs($fixture->user)
            ->get(route('entities.templateChanges.preview', ['vault' => $fixture->vault, 'entityType' => $fixture->animals, 'from' => $fixture->horse->uuid, 'to' => $fixture->pet->uuid]))
            ->assertStatus(200)
            ->assertSee('Monica keeps these values.')
            ->assertSee('Move 4 Animals');
    }

    #[Test]
    public function it_confirms_the_change_and_opens_its_result(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();

        $response = $this->actingAs($fixture->user)
            ->post(route('entities.templateChanges.create', ['vault' => $fixture->vault, 'entityType' => $fixture->animals]), [
                'from' => $fixture->horse->uuid,
                'to' => $fixture->pet->uuid,
            ]);

        $change = TemplateChange::query()->firstOrFail();
        $response->assertRedirect(route('entities.templateChanges.show', ['vault' => $fixture->vault, 'entityType' => $fixture->animals, 'templateChange' => $change]));

        $this->actingAs($fixture->user)
            ->get(route('entities.templateChanges.show', ['vault' => $fixture->vault, 'entityType' => $fixture->animals, 'templateChange' => $change]))
            ->assertStatus(200)
            ->assertSee('0 of 4 Animals dealt with so far.');
    }

    #[Test]
    public function it_returns_a_refusal_to_the_first_step(): void
    {
        $fixture = AnimalsVault::build();
        $fixture->pet->update(['is_active' => false]);

        $this->actingAs($fixture->user)
            ->post(route('entities.templateChanges.create', ['vault' => $fixture->vault, 'entityType' => $fixture->animals]), [
                'from' => $fixture->horse->uuid,
                'to' => $fixture->pet->uuid,
            ])
            ->assertSessionHasErrors(['to' => 'This template is turned off, so no record can be put on it.']);
    }

    #[Test]
    public function it_requires_a_destination(): void
    {
        $fixture = AnimalsVault::build();

        $this->actingAs($fixture->user)
            ->post(route('entities.templateChanges.create', ['vault' => $fixture->vault, 'entityType' => $fixture->animals]), [
                'from' => $fixture->horse->uuid,
            ])
            ->assertSessionHasErrors('to');
    }

    #[Test]
    public function it_does_not_find_a_change_through_another_vault(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $other = AnimalsVault::build($fixture->user);
        $change = TemplateChange::factory()->create(['vault_id' => $fixture->vault->id, 'entity_type_id' => $fixture->animals->id]);

        $this->actingAs($fixture->user)
            ->get(route('entities.templateChanges.show', ['vault' => $other->vault, 'entityType' => $fixture->animals, 'templateChange' => $change]))
            ->assertNotFound();
    }
}
