<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\ContactDuplicateCandidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDuplicateCandidateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account_and_to_a_vault(): void
    {
        $candidate = ContactDuplicateCandidate::factory()->create();

        $this->assertTrue($candidate->account()->exists());
        $this->assertTrue($candidate->vault()->exists());
    }

    #[Test]
    public function it_belongs_to_the_two_contacts_of_the_pair(): void
    {
        $candidate = ContactDuplicateCandidate::factory()->create();

        $this->assertInstanceOf(Contact::class, $candidate->contact);
        $this->assertInstanceOf(Contact::class, $candidate->otherContact);
        $this->assertEquals($candidate->contact->vault_id, $candidate->otherContact->vault_id);
    }

    #[Test]
    public function it_belongs_to_the_user_who_ruled_it_out(): void
    {
        $user = User::factory()->create();
        $candidate = ContactDuplicateCandidate::factory()->dismissedBy($user)->create();

        $this->assertInstanceOf(User::class, $candidate->dismissedBy);
    }

    #[Test]
    public function it_knows_whether_it_was_ruled_out(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(ContactDuplicateCandidate::factory()->create()->isDismissed());
        $this->assertTrue(ContactDuplicateCandidate::factory()->dismissedBy($user)->create()->isDismissed());
    }

    #[Test]
    public function it_offers_only_the_pairs_nobody_ruled_out(): void
    {
        $user = User::factory()->create();
        ContactDuplicateCandidate::factory()->create();
        ContactDuplicateCandidate::factory()->dismissedBy($user)->create();

        $this->assertEquals(1, ContactDuplicateCandidate::query()->standing()->count());
        $this->assertEquals(2, ContactDuplicateCandidate::query()->count());
    }

    #[Test]
    public function it_offers_only_the_pairs_whose_two_contacts_are_still_there(): void
    {
        ContactDuplicateCandidate::factory()->create();
        $gone = ContactDuplicateCandidate::factory()->create();
        $gone->otherContact->delete();

        $this->assertEquals(1, ContactDuplicateCandidate::query()->whereBothContactsExist()->count());
        $this->assertEquals(2, ContactDuplicateCandidate::query()->count());
    }

    #[Test]
    public function it_knows_whether_both_its_contacts_are_still_there(): void
    {
        $candidate = ContactDuplicateCandidate::factory()->create();

        $this->assertTrue($candidate->hasBothContacts());

        $candidate->otherContact->delete();

        $this->assertFalse($candidate->fresh()->hasBothContacts());
    }

    #[Test]
    public function it_reads_its_reasons_as_a_list(): void
    {
        $candidate = ContactDuplicateCandidate::factory()->sharingAValue()->create();

        $this->assertEquals('field', $candidate->reasons[0]['kind']);
        $this->assertEquals('email', $candidate->reasons[0]['field_key']);
        $this->assertEquals(95, $candidate->score);
    }
}
