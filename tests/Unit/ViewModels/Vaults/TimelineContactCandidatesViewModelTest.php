<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\TimelineContactCandidatesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TimelineContactCandidatesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_only_the_people_who_took_part_in_a_life_event_of_the_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = $this->contactOn($vault, 'Dwight', 'Schrute');
        $creed = $this->contactOn($vault, 'Creed', 'Bratton');
        $toby = $this->contactOn($vault, 'Toby', 'Flenderson');
        $this->contactOn($vault, 'Kevin', 'Malone');
        $karen = $this->contactOn(Vault::factory()->create(['account_id' => $user->account_id]), 'Karen', 'Filippelli');

        $this->tookPart($dwight);
        $this->tookPart($creed)->delete();
        $this->tookPart($karen);
        $this->tookPart($toby);

        $viewModel = new TimelineContactCandidatesViewModel(user: $user, vault: $vault, query: '');

        $this->assertSame(['Toby Flenderson', 'Dwight Schrute'], array_column($viewModel->candidates(), 'name'));
        $this->assertSame($dwight->uuid, $viewModel->candidates()[1]['uuid']);
    }

    #[Test]
    public function it_narrows_to_what_was_typed_ten_at_most(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        for ($index = 1; $index <= 11; $index++) {
            $this->tookPart($this->contactOn($vault, 'Stanley', "Hudson {$index}"));
        }

        $this->tookPart($this->contactOn($vault, 'Phyllis', 'Vance'));

        $candidates = new TimelineContactCandidatesViewModel(user: $user, vault: $vault, query: 'stan')->candidates();

        $this->assertCount(10, $candidates);
        $this->assertNotContains('Phyllis Vance', array_column($candidates, 'name'));
    }

    #[Test]
    public function it_says_what_matched_nothing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertSame('Nobody with a life event matches “Bob”.', new TimelineContactCandidatesViewModel(user: $user, vault: $vault, query: 'Bob')->noCandidatesLine());
    }

    private function contactOn(Vault $vault, string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function tookPart(Contact $contact): LifeEvent
    {
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $contact->vault_id]);

        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $contact->id]);

        return $lifeEvent;
    }
}
