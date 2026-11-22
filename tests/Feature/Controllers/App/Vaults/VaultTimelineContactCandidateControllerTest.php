<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultTimelineContactCandidateControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_with_the_people_matching_what_was_typed(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $dwight->id]);

        $this->actingAs($user)
            ->get(route('vaults.timelineContactCandidates.index', ['vault' => $vault, 'query' => 'dwi']))
            ->assertOk()
            ->assertSee('id="record-choices"', escape: false)
            ->assertSee('Dwight Schrute');
    }
}
