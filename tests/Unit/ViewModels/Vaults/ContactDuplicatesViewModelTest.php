<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\ContactDuplicateCandidate;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactDuplicatesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDuplicatesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_pairs_of_its_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->candidateIn($vault);

        $pairs = new ContactDuplicatesViewModel(user: $user, vault: $vault)->pairs();

        $this->assertCount(1, $pairs);
    }

    #[Test]
    public function it_lists_nothing_from_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->candidateIn($elsewhere);

        $pairs = new ContactDuplicatesViewModel(user: $user, vault: $vault)->pairs();

        $this->assertCount(0, $pairs);
    }

    #[Test]
    public function it_leaves_out_a_pair_somebody_ruled_out(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $candidate = $this->candidateIn($vault);
        $candidate->dismissed_at = now();
        $candidate->save();

        $pairs = new ContactDuplicatesViewModel(user: $user, vault: $vault)->pairs();

        $this->assertCount(0, $pairs);
    }

    #[Test]
    public function it_puts_the_strongest_resemblance_first(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->candidateIn($vault, score: 62);
        $strongest = $this->candidateIn($vault, score: 97);

        $pairs = new ContactDuplicatesViewModel(user: $user, vault: $vault)->pairs();

        $this->assertEquals($strongest->id, $pairs->first()->id);
    }

    #[Test]
    public function it_says_in_words_why_a_pair_is_on_the_screen(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $candidate = $this->candidateIn($vault);

        $row = new ContactDuplicatesViewModel(user: $user, vault: $vault)->row($candidate);

        $this->assertEquals('their names are alike', $row['why']);
        $this->assertStringContainsString('/merge/', $row['compare_url']);
        $this->assertStringContainsString('/dismissal', $row['dismiss_url']);
    }

    #[Test]
    public function it_names_the_shared_value_when_that_is_what_matched(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $candidate = $this->candidateIn($vault, sharing: true);

        $row = new ContactDuplicatesViewModel(user: $user, vault: $vault)->row($candidate);

        $this->assertStringContainsString('jim@dundermifflin.com', $row['why']);
    }

    private function candidateIn(Vault $vault, int $score = 70, bool $sharing = false): ContactDuplicateCandidate
    {
        $one = Contact::factory()->create(['vault_id' => $vault->id]);
        $two = Contact::factory()->create(['vault_id' => $vault->id]);

        $factory = ContactDuplicateCandidate::factory();

        if ($sharing) {
            $factory = $factory->sharingAValue();
        }

        return $factory->create([
            'contact_id' => min($one->id, $two->id),
            'other_contact_id' => max($one->id, $two->id),
            'score' => $score,
        ]);
    }
}
