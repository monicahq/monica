<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DismissDuplicateCandidate;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ContactDuplicateCandidate;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DismissDuplicateCandidateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_that_the_pair_is_two_different_people(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $candidate = $this->candidateOf($user);

        $dismissed = new DismissDuplicateCandidate(user: $user, candidate: $candidate)->execute();

        $this->assertInstanceOf(ContactDuplicateCandidate::class, $dismissed);
        $this->assertDatabaseHas('contact_duplicate_candidates', [
            'id' => $candidate->id,
            'dismissed_by_user_id' => $user->id,
        ]);
        $this->assertNotNull($dismissed->dismissed_at);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::DuplicateCandidateDismissed);
    }

    #[Test]
    public function it_keeps_the_row_so_the_pair_is_never_raised_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $candidate = $this->candidateOf($user);

        new DismissDuplicateCandidate(user: $user, candidate: $candidate)->execute();

        $this->assertDatabaseCount('contact_duplicate_candidates', 1);
        $this->assertEquals(0, ContactDuplicateCandidate::query()->standing()->count());
    }

    #[Test]
    public function it_leaves_a_pair_that_was_already_dismissed_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $other = User::factory()->create();
        $candidate = $this->candidateOf($user);
        $candidate->dismissed_at = now()->subDay();
        $candidate->dismissed_by_user_id = $other->id;
        $candidate->save();

        $was = $candidate->dismissed_at;

        new DismissDuplicateCandidate(user: $user, candidate: $candidate)->execute();

        $this->assertEquals($was->toIso8601String(), $candidate->fresh()->dismissed_at->toIso8601String());
        $this->assertEquals($other->id, $candidate->fresh()->dismissed_by_user_id);
    }

    #[Test]
    public function it_refuses_a_pair_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $stranger = ContactDuplicateCandidate::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DismissDuplicateCandidate(user: $user, candidate: $stranger)->execute();
    }

    #[Test]
    public function it_refuses_a_pair_one_of_whose_contacts_is_gone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $candidate = $this->candidateOf($user);
        $candidate->otherContact->delete();

        $this->expectException(ModelNotFoundException::class);

        new DismissDuplicateCandidate(user: $user, candidate: $candidate->fresh())->execute();
    }

    private function candidateOf(User $user): ContactDuplicateCandidate
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $jimmy = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jimmy', 'last_name' => 'Halpert']);

        return ContactDuplicateCandidate::factory()->create([
            'contact_id' => min($jim->id, $jimmy->id),
            'other_contact_id' => max($jim->id, $jimmy->id),
        ]);
    }
}
