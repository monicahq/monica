<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ContactLogHelper;
use App\Models\Contact;
use App\Models\ContactLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactLogHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function contact_created(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_created',
            'objects' => json_encode([]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Created the contact',
            $sentence
        );
    }

    /** @test */
    public function contact_updated(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_updated',
            'objects' => json_encode([]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Updated the contact',
            $sentence
        );
    }

    /** @test */
    public function contact_copied_to_another_vault(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_copied_to_another_vault',
            'objects' => json_encode([
                'name' => 'Vault',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Copied the contact to the vault Vault',
            $sentence
        );
    }

    /** @test */
    public function contact_moved_to_another_vault(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_moved_to_another_vault',
            'objects' => json_encode([
                'initial_vault_name' => 'Original vault',
                'destination_vault_name' => 'Target vault',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Moved the contact from the vault Original vault to the vault Target vault',
            $sentence
        );
    }

    /** @test */
    public function relationship_set(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'relationship_set',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'relationship_name' => 'Father',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Set the contact Monica Geller (deleted) as Father',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = ContactLog::factory()->create([
            'action_name' => 'relationship_set',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->name,
                'relationship_name' => 'Father',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Set the contact <a href="'.$url.'">'.$contact->name.'</a> as Father',
            $sentence
        );
    }

    /** @test */
    public function relationship_unset(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'relationship_unset',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'relationship_name' => 'Father',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Removed the contact Monica Geller (deleted) as Father',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = ContactLog::factory()->create([
            'action_name' => 'relationship_unset',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->name,
                'relationship_name' => 'Father',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Removed the contact <a href="'.$url.'">'.$contact->name.'</a> as Father',
            $sentence
        );
    }

    /** @test */
    public function pronoun_assigned(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'pronoun_assigned',
            'objects' => json_encode([
                'pronoun_name' => 'She/He',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Assigned the pronoun She/He',
            $sentence
        );
    }

    /** @test */
    public function pronoun_removed(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'pronoun_removed',
            'objects' => json_encode([
                'pronoun_name' => 'She/He',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Removed the pronoun She/He',
            $sentence
        );
    }

    /** @test */
    public function label_assigned(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'label_assigned',
            'objects' => json_encode([
                'label_name' => 'Label name',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Assigned the label Label name',
            $sentence
        );
    }

    /** @test */
    public function label_removed(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'label_removed',
            'objects' => json_encode([
                'label_name' => 'Label name',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Removed the label Label name',
            $sentence
        );
    }

    /** @test */
    public function contact_information_created(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_information_created',
            'objects' => json_encode([
                'contact_information_type_name' => 'Facebook',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Added the contact information Facebook',
            $sentence
        );
    }

    /** @test */
    public function contact_information_updated(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_information_updated',
            'objects' => json_encode([
                'contact_information_type_name' => 'Facebook',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Updated the contact information Facebook',
            $sentence
        );
    }

    /** @test */
    public function contact_information_destroyed(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_information_destroyed',
            'objects' => json_encode([
                'contact_information_type_name' => 'Facebook',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Deleted the contact information Facebook',
            $sentence
        );
    }

    /** @test */
    public function contact_address_created(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_address_created',
            'objects' => json_encode([
                'address_type_name' => 'Home',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Added the contact address Home',
            $sentence
        );
    }

    /** @test */
    public function contact_address_updated(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_address_updated',
            'objects' => json_encode([
                'address_type_name' => 'Home',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Updated the contact address Home',
            $sentence
        );
    }

    /** @test */
    public function contact_address_destroyed(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_address_destroyed',
            'objects' => json_encode([
                'address_type_name' => 'Home',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Deleted the contact address Home',
            $sentence
        );
    }

    /** @test */
    public function note_created(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'note_created',
            'objects' => json_encode([]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Wrote a note',
            $sentence
        );
    }

    /** @test */
    public function note_updated(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'note_updated',
            'objects' => json_encode([]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Updated a note',
            $sentence
        );
    }

    /** @test */
    public function note_destroyed(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'note_destroyed',
            'objects' => json_encode([]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Deleted a note',
            $sentence
        );
    }

    /** @test */
    public function contact_template_updated(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'contact_template_updated',
            'objects' => json_encode([]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Changed the template used to display the contact',
            $sentence
        );
    }

    /** @test */
    public function loan_created(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'loan_created',
            'objects' => json_encode([
                'loan_name' => 'Home',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Added a loan called Home',
            $sentence
        );
    }

    /** @test */
    public function loan_updated(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'loan_updated',
            'objects' => json_encode([
                'loan_name' => 'Home',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Updated the loan called Home',
            $sentence
        );
    }

    /** @test */
    public function loan_destroyed(): void
    {
        $log = ContactLog::factory()->create([
            'action_name' => 'loan_destroyed',
            'objects' => json_encode([
                'loan_name' => 'Home',
            ]),
        ]);

        $sentence = ContactLogHelper::process($log);
        $this->assertEquals(
            'Deleted the loan called Home',
            $sentence
        );
    }
}
