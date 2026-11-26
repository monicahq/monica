<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\DestroyContact;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultTrashViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultTrashViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_how_long_ago_each_item_was_deleted_and_how_long_it_still_waits(): void
    {
        $this->travelTo('2026-11-25 10:00:00');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subDays(3)]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subDays(15)]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subDays(28)]);

        $rows = new VaultTrashViewModel(user: $user, vault: $vault, kind: null)->rows();

        $this->assertSame(['Today', '3 days ago', '2 weeks ago', '4 weeks ago'], array_column($rows, 'deletedAgo'));
        $this->assertSame([false, false, false, true], array_column($rows, 'isUrgent'));
        $this->assertStringStartsWith('2 days left. Deleted for good on ', $rows[3]['tooltip']);
    }

    #[Test]
    public function it_offers_every_kind_present_with_its_count_and_names_what_goes_with_a_person(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        Relationship::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'contact_a_id' => $jan->id]);
        $lifeEvent = LifeEvent::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $jan->id]);
        new DestroyContact(user: $user, contact: $jan)->execute();

        $viewModel = new VaultTrashViewModel(user: $user, vault: $vault, kind: null);

        $this->assertSame(
            [['all', 'Everything', 1], ['contact', 'People', 1], ['relationship', 'Relationships', 1], ['life-event', 'Life events', 1]],
            array_map(fn (array $entry): array => [$entry['key'], $entry['label'], $entry['count']], $viewModel->menu()),
        );
        $this->assertSame(['What was deleted at the same time goes with it: 1 relationship and 1 life event.'], $viewModel->rows()[0]['destroyLines']);
        $this->assertSame('Keep them', $viewModel->rows()[0]['keepLabel']);
        $this->assertSame(['This deletes 1 person, 1 relationship and 1 life event for good.'], $viewModel->emptyLines());
    }

    #[Test]
    public function it_knows_when_the_trash_holds_nothing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue(new VaultTrashViewModel(user: $user, vault: $vault, kind: null)->holdsNothing());
    }
}
