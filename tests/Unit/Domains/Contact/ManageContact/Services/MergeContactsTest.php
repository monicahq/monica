<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\MergeContacts;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MergeContactsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_merges_two_contacts(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);

        $primaryContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $duplicateContact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($user, $user->account, $vault, $primaryContact, $duplicateContact);
    }

    /** @test */
    public function it_deduplicates_contact_information(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);

        $primaryContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $duplicateContact = Contact::factory()->create(['vault_id' => $vault->id]);

        $emailType = ContactInformationType::factory()->create([
            'account_id' => $user->account_id,
            'type' => 'email',
            'can_be_deleted' => false,
        ]);

        $phoneType = ContactInformationType::factory()->create([
            'account_id' => $user->account_id,
            'type' => 'phone',
            'can_be_deleted' => false,
        ]);

        ContactInformation::factory()->create([
            'contact_id' => $primaryContact->id,
            'type_id' => $emailType->id,
            'data' => 'test@example.com',
        ]);

        ContactInformation::factory()->create([
            'contact_id' => $duplicateContact->id,
            'type_id' => $emailType->id,
            'data' => 'test@example.com',
        ]);

        ContactInformation::factory()->create([
            'contact_id' => $duplicateContact->id,
            'type_id' => $phoneType->id,
            'data' => '1234567890',
        ]);

        $this->executeService($user, $user->account, $vault, $primaryContact, $duplicateContact);

        $primaryContact->refresh();

        $this->assertCount(2, $primaryContact->contactInformations);

        $emails = $primaryContact->contactInformations->filter(fn ($info) => $info->type_id === $emailType->id);
        $this->assertCount(1, $emails);
        $this->assertEquals('test@example.com', $emails->first()->data);

        $phones = $primaryContact->contactInformations->filter(fn ($info) => $info->type_id === $phoneType->id);
        $this->assertCount(1, $phones);
        $this->assertEquals('1234567890', $phones->first()->data);
    }

    /** @test */
    public function it_deduplicates_reminders(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);

        $primaryContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $duplicateContact = Contact::factory()->create(['vault_id' => $vault->id]);

        ContactReminder::factory()->create([
            'contact_id' => $primaryContact->id,
            'label' => 'Birthday',
            'day' => 15,
            'month' => 6,
            'year' => 1990,
            'type' => ContactReminder::TYPE_RECURRING_YEAR,
            'frequency_number' => 1,
        ]);

        ContactReminder::factory()->create([
            'contact_id' => $duplicateContact->id,
            'label' => 'Birthday',
            'day' => 15,
            'month' => 6,
            'year' => 1990,
            'type' => ContactReminder::TYPE_RECURRING_YEAR,
            'frequency_number' => 1,
        ]);

        ContactReminder::factory()->create([
            'contact_id' => $duplicateContact->id,
            'label' => 'Anniversary',
            'day' => 20,
            'month' => 10,
            'year' => 2015,
            'type' => ContactReminder::TYPE_RECURRING_YEAR,
            'frequency_number' => 1,
        ]);

        $this->executeService($user, $user->account, $vault, $primaryContact, $duplicateContact);

        $primaryContact->refresh();

        $this->assertCount(2, $primaryContact->reminders);

        $birthdayReminders = $primaryContact->reminders->filter(fn ($reminder) => $reminder->label === 'Birthday');
        $this->assertCount(1, $birthdayReminders);

        $anniversaryReminders = $primaryContact->reminders->filter(fn ($reminder) => $reminder->label === 'Anniversary');
        $this->assertCount(1, $anniversaryReminders);
    }

    /** @test */
    public function it_hides_duplicate_contact_after_merge(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);

        $primaryContact = Contact::factory()->create(['vault_id' => $vault->id, 'listed' => true]);
        $duplicateContact = Contact::factory()->create(['vault_id' => $vault->id, 'listed' => true]);

        $this->assertTrue($duplicateContact->listed);
        $this->assertTrue($duplicateContact->shouldBeSearchable());

        $this->executeService($user, $user->account, $vault, $primaryContact, $duplicateContact);

        $duplicateContact->refresh();
        $primaryContact->refresh();

        $this->assertFalse($duplicateContact->listed);
        $this->assertFalse($duplicateContact->shouldBeSearchable());
        $this->assertTrue($primaryContact->listed);

        $listedContacts = Contact::where('vault_id', $vault->id)
            ->where('listed', true)
            ->get();

        $this->assertCount(1, $listedContacts);
        $this->assertEquals($primaryContact->id, $listedContacts->first()->id);
    }

    /** @test */
    public function it_prevents_self_referencing_relationships(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);

        $primaryContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $duplicateContact = Contact::factory()->create(['vault_id' => $vault->id]);

        $primaryContact->relationships()->attach($duplicateContact->id);
        $this->assertCount(1, $primaryContact->relationships);
        $this->assertEquals($duplicateContact->id, $primaryContact->relationships->first()->id);

        $this->executeService($user, $user->account, $vault, $primaryContact, $duplicateContact);

        $primaryContact->refresh();

        $this->assertCount(0, $primaryContact->relationships);

        $selfReference = \DB::table('relationships')
            ->where('contact_id', $primaryContact->id)
            ->where('related_contact_id', $primaryContact->id)
            ->exists();

        $this->assertFalse($selfReference);
    }

    /** @test */
    public function it_prevents_duplicate_relationships(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);

        $primaryContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $duplicateContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherContact = Contact::factory()->create(['vault_id' => $vault->id]);

        $primaryContact->relationships()->attach($otherContact->id);
        $duplicateContact->relationships()->attach($otherContact->id);

        $this->assertCount(1, $primaryContact->relationships);
        $this->assertCount(1, $duplicateContact->relationships);

        $this->executeService($user, $user->account, $vault, $primaryContact, $duplicateContact);

        $primaryContact->refresh();

        $this->assertCount(1, $primaryContact->relationships);
        $this->assertEquals($otherContact->id, $primaryContact->relationships->first()->id);
    }

    /** @test */
    public function it_merges_incoming_relationships_without_duplication(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);

        $primaryContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $duplicateContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherContact = Contact::factory()->create(['vault_id' => $vault->id]);

        $otherContact->relationships()->attach($primaryContact->id);
        $otherContact->relationships()->attach($duplicateContact->id);

        $this->assertCount(1, $otherContact->relationships);

        $incomingToPrimary = \DB::table('relationships')
            ->where('related_contact_id', $primaryContact->id)
            ->count();
        $this->assertEquals(1, $incomingToPrimary);

        $incomingToDuplicate = \DB::table('relationships')
            ->where('related_contact_id', $duplicateContact->id)
            ->count();
        $this->assertEquals(1, $incomingToDuplicate);

        $this->executeService($user, $user->account, $vault, $primaryContact, $duplicateContact);

        $otherContact->refresh();
        $this->assertCount(1, $otherContact->relationships);
        $this->assertEquals($primaryContact->id, $otherContact->relationships->first()->id);

        $incomingToPrimary = \DB::table('relationships')
            ->where('related_contact_id', $primaryContact->id)
            ->count();
        $this->assertEquals(1, $incomingToPrimary);

        $incomingToDuplicate = \DB::table('relationships')
            ->where('related_contact_id', $duplicateContact->id)
            ->count();
        $this->assertEquals(0, $incomingToDuplicate);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $this->expectException(ValidationException::class);
        (new MergeContacts)->execute(['title' => 'Ross']);
    }

    /** @test */
    public function it_fails_if_contacts_are_the_same(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(ModelNotFoundException::class);

        (new MergeContacts)->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'primary_contact_id' => $contact->id,
            'duplicate_contact_id' => $contact->id,
        ]);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_VIEW, $vault);
        $primaryContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $duplicateContact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(NotEnoughPermissionException::class);

        (new MergeContacts)->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'primary_contact_id' => $primaryContact->id,
            'duplicate_contact_id' => $duplicateContact->id,
        ]);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $primaryContact, Contact $duplicateContact): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'primary_contact_id' => $primaryContact->id,
            'duplicate_contact_id' => $duplicateContact->id,
        ];

        $mergedContact = (new MergeContacts)->execute($request);

        $this->assertInstanceOf(Contact::class, $mergedContact);
        $this->assertEquals($primaryContact->id, $mergedContact->id);
    }
}
