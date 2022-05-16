<?php

namespace Tests\Unit\Helpers;

use App\Helpers\AuditLogHelper;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuditLogHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function account_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'account_created',
            'objects' => json_encode([]),
        ]);
        $user = User::factory()->create();

        $sentence = AuditLogHelper::process($log, $user);

        $this->assertIsString($sentence);
        $this->assertEquals(
            'Created the account',
            $sentence
        );
    }

    /** @test */
    public function vault_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'vault_created',
            'objects' => json_encode([
                'vault_id' => 321,
                'vault_name' => 'Mon vault',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the vault Mon vault (deleted)',
            $sentence
        );

        $vault = Vault::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'vault_created',
            'objects' => json_encode([
                'vault_id' => $vault->id,
                'vault_name' => 'Mon vault',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$vault->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the vault <a href="'.$url.'">'.$vault->name.'</a>',
            $sentence
        );
    }

    /** @test */
    public function vault_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'vault_updated',
            'objects' => json_encode([
                'vault_id' => 321,
                'vault_name' => 'Mon vault',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the vault Mon vault (deleted)',
            $sentence
        );

        $vault = Vault::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'vault_updated',
            'objects' => json_encode([
                'vault_id' => $vault->id,
                'vault_name' => 'Mon vault',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$vault->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the vault <a href="'.$url.'">'.$vault->name.'</a>',
            $sentence
        );
    }

    /** @test */
    public function vault_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'vault_destroyed',
            'objects' => json_encode([
                'vault_name' => 'Mon vault',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the vault Mon vault',
            $sentence
        );
    }

    /** @test */
    public function vault_access_grant(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'vault_access_grant',
            'objects' => json_encode([
                'user_name' => 'Regis troyat',
                'vault_name' => 'My Vault',
                'permission_type' => 'Manager',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Grant the Manager permission to Regis troyat to the vault My Vault',
            $sentence
        );
    }

    /** @test */
    public function vault_access_permission_changed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'vault_access_permission_changed',
            'objects' => json_encode([
                'user_name' => 'Regis troyat',
                'vault_name' => 'My Vault',
                'permission_type' => 'Manager',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Changed the permission of Regis troyat to Manager in the vault My Vault',
            $sentence
        );
    }

    /** @test */
    public function gender_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'gender_created',
            'objects' => json_encode([
                'gender_name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the gender Male',
            $sentence
        );
    }

    /** @test */
    public function gender_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'gender_updated',
            'objects' => json_encode([
                'gender_name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the gender Male',
            $sentence
        );
    }

    /** @test */
    public function gender_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'gender_destroyed',
            'objects' => json_encode([
                'gender_name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the gender Male',
            $sentence
        );
    }

    /** @test */
    public function label_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'label_created',
            'objects' => json_encode([
                'label_name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the label Male',
            $sentence
        );
    }

    /** @test */
    public function label_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'label_updated',
            'objects' => json_encode([
                'label_name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the label Male',
            $sentence
        );
    }

    /** @test */
    public function label_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'label_destroyed',
            'objects' => json_encode([
                'label_name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the label Male',
            $sentence
        );
    }

    /** @test */
    public function contact_information_type_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_information_type_created',
            'objects' => json_encode([
                'name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the contact information type Male',
            $sentence
        );
    }

    /** @test */
    public function contact_information_type_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_information_type_updated',
            'objects' => json_encode([
                'name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the contact information type Male',
            $sentence
        );
    }

    /** @test */
    public function contact_information_type_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_information_type_destroyed',
            'objects' => json_encode([
                'name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the contact information type Male',
            $sentence
        );
    }

    /** @test */
    public function address_type_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'address_type_created',
            'objects' => json_encode([
                'name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the address type Male',
            $sentence
        );
    }

    /** @test */
    public function address_type_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'address_type_updated',
            'objects' => json_encode([
                'name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the address type Male',
            $sentence
        );
    }

    /** @test */
    public function address_type_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'address_type_destroyed',
            'objects' => json_encode([
                'name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the address type Male',
            $sentence
        );
    }

    /** @test */
    public function pronoun_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'pronoun_created',
            'objects' => json_encode([
                'pronoun_name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the pronoun Male',
            $sentence
        );
    }

    /** @test */
    public function pronoun_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'pronoun_updated',
            'objects' => json_encode([
                'pronoun_name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the pronoun Male',
            $sentence
        );
    }

    /** @test */
    public function pronoun_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'pronoun_destroyed',
            'objects' => json_encode([
                'pronoun_name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the pronoun Male',
            $sentence
        );
    }

    /** @test */
    public function relationship_group_type_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_group_type_created',
            'objects' => json_encode([
                'name' => 'Male',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the relationship group type Male',
            $sentence
        );
    }

    /** @test */
    public function relationship_group_type_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_group_type_updated',
            'objects' => json_encode([
                'name' => 'Family',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the relationship group type Family',
            $sentence
        );
    }

    /** @test */
    public function relationship_group_type_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_group_type_destroyed',
            'objects' => json_encode([
                'name' => 'Family',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the relationship group type Family',
            $sentence
        );
    }

    /** @test */
    public function relationship_type_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_type_created',
            'objects' => json_encode([
                'name' => 'Father',
                'group_type_name' => 'Family',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the relationship type Father in the group type Family',
            $sentence
        );
    }

    /** @test */
    public function relationship_type_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_type_updated',
            'objects' => json_encode([
                'name' => 'Father',
                'group_type_name' => 'Family',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the relationship type Father in the group type Family',
            $sentence
        );
    }

    /** @test */
    public function relationship_type_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_type_destroyed',
            'objects' => json_encode([
                'name' => 'Father',
                'group_type_name' => 'Family',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the relationship type Father in the group type Family',
            $sentence
        );
    }

    /** @test */
    public function administrator_privilege_given(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'administrator_privilege_given',
            'objects' => json_encode([
                'user_id' => 321,
                'user_name' => 'Regis troyat',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Gave administrator privilege to Regis troyat',
            $sentence
        );

        $user = User::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'administrator_privilege_given',
            'objects' => json_encode([
                'user_id' => $user->id,
                'user_name' => 'Alexis troyat',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Gave administrator privilege to '.$user->name,
            $sentence
        );
    }

    /** @test */
    public function administrator_privilege_removed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'administrator_privilege_removed',
            'objects' => json_encode([
                'user_id' => 321,
                'user_name' => 'Regis troyat',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Removed administrator privilege of Regis troyat',
            $sentence
        );

        $user = User::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'administrator_privilege_removed',
            'objects' => json_encode([
                'user_id' => $user->id,
                'user_name' => 'Alexis troyat',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Removed administrator privilege of '.$user->name,
            $sentence
        );
    }

    /** @test */
    public function contact_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_created',
            'objects' => json_encode([
                'id' => 321,
                'name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_created',
            'objects' => json_encode([
                'id' => $contact->id,
                'name' => 'Monica Geller',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_updated',
            'objects' => json_encode([
                'id' => 321,
                'name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_updated',
            'objects' => json_encode([
                'id' => $contact->id,
                'name' => 'Monica Geller',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_destroyed',
            'objects' => json_encode([
                'name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the contact Monica Geller',
            $sentence
        );
    }

    /** @test */
    public function contact_copied_to_another_vault(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_copied_to_another_vault',
            'objects' => json_encode([
                'original_vault_name' => 'Original vault',
                'target_vault_name' => 'Target vault',
                'contact_id' => 134,
                'contact_name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Copied the contact Monica Geller (deleted) from the vault Original vault to the vault Target vault',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_copied_to_another_vault',
            'objects' => json_encode([
                'original_vault_name' => 'Original vault',
                'target_vault_name' => 'Target vault',
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Copied the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a> from the vault Original vault to the vault Target vault',
            $sentence
        );
    }

    /** @test */
    public function contact_moved_to_another_vault(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_moved_to_another_vault',
            'objects' => json_encode([
                'original_vault_name' => 'Original vault',
                'target_vault_name' => 'Target vault',
                'contact_id' => 134,
                'contact_name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Moved the contact Monica Geller (deleted) from the vault Original vault to the vault Target vault',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_moved_to_another_vault',
            'objects' => json_encode([
                'original_vault_name' => 'Original vault',
                'target_vault_name' => 'Target vault',
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Moved the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a> from the vault Original vault to the vault Target vault',
            $sentence
        );
    }

    /** @test */
    public function relationship_set(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_set',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'other_contact_id' => 345,
                'other_contact_name' => 'Ross Bing',
                'relationship_name' => 'Father',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Set the contact Monica Geller (deleted) as Father of Ross Bing (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_set',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'other_contact_id' => 345,
                'other_contact_name' => 'Ross Bing',
                'relationship_name' => 'Father',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Set the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a> as Father of Ross Bing (deleted)',
            $sentence
        );

        $otherContact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_set',
            'objects' => json_encode([
                'contact_id' => 334,
                'contact_name' => 'Jess',
                'other_contact_id' => $otherContact->id,
                'other_contact_name' => $otherContact->name,
                'relationship_name' => 'Father',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$otherContact->vault->id.'/contacts/'.$otherContact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Set the contact Jess (deleted) as Father of <a href="'.$url.'">'.$otherContact->name.'</a>',
            $sentence
        );

        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_set',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'other_contact_id' => $otherContact->id,
                'other_contact_name' => $otherContact->name,
                'relationship_name' => 'Father',
            ]),
        ]);

        $urlContact = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $urlOtherContact = env('APP_URL').'/vaults/'.$otherContact->vault->id.'/contacts/'.$otherContact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Set the contact <a href="'.$urlContact.'">'.$contact->getName($loggedUser).'</a> as Father of <a href="'.$urlOtherContact.'">'.$otherContact->name.'</a>',
            $sentence
        );
    }

    /** @test */
    public function relationship_unset(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_unset',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'other_contact_id' => 345,
                'other_contact_name' => 'Ross Bing',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Unset the contact Monica Geller (deleted) as related to Ross Bing (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_unset',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'other_contact_id' => 345,
                'other_contact_name' => 'Ross Bing',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Unset the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a> as related to Ross Bing (deleted)',
            $sentence
        );

        $otherContact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_unset',
            'objects' => json_encode([
                'contact_id' => 334,
                'contact_name' => 'Jess',
                'other_contact_id' => $otherContact->id,
                'other_contact_name' => $otherContact->name,
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$otherContact->vault->id.'/contacts/'.$otherContact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Unset the contact Jess (deleted) as related to <a href="'.$url.'">'.$otherContact->name.'</a>',
            $sentence
        );

        $log = AuditLog::factory()->create([
            'action_name' => 'relationship_unset',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'other_contact_id' => $otherContact->id,
                'other_contact_name' => $otherContact->name,
            ]),
        ]);

        $urlContact = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $urlOtherContact = env('APP_URL').'/vaults/'.$otherContact->vault->id.'/contacts/'.$otherContact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Unset the contact <a href="'.$urlContact.'">'.$contact->getName($loggedUser).'</a> as related to <a href="'.$urlOtherContact.'">'.$otherContact->name.'</a>',
            $sentence
        );
    }

    /** @test */
    public function pronoun_set(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'pronoun_set',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'pronoun_name' => 'She/He',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Set the pronoun She/He to the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'pronoun_set',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'pronoun_name' => 'She/He',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Set the pronoun She/He to the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function pronoun_unset(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'pronoun_unset',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Unset the pronoun of the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'pronoun_unset',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Unset the pronoun of the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function label_assigned(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'label_assigned',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'label_name' => 'Label name',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Assigned the label Label name to the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'label_assigned',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'label_name' => 'Label name',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Assigned the label Label name to the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function label_removed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'label_removed',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'label_name' => 'Label name',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Removed the label Label name from the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'label_removed',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'label_name' => 'Label name',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Removed the label Label name from the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_information_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_information_created',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'contact_information_type_name' => 'Facebook',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the contact information Facebook for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_information_created',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'contact_information_type_name' => 'Facebook',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the contact information Facebook for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_information_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_information_updated',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'contact_information_type_name' => 'Facebook',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the contact information Facebook for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_information_updated',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'contact_information_type_name' => 'Facebook',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the contact information Facebook for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_information_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_information_destroyed',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'contact_information_type_name' => 'Facebook',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the contact information Facebook for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_information_destroyed',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'contact_information_type_name' => 'Facebook',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the contact information Facebook for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_address_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_address_created',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'address_type_name' => 'Home',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the contact address Home for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_address_created',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'address_type_name' => 'Home',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the contact address Home for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_address_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_address_updated',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'address_type_name' => 'Home',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the contact address Home for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_address_updated',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'address_type_name' => 'Home',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the contact address Home for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_address_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_address_destroyed',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'address_type_name' => 'Home',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted a contact address for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_address_destroyed',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'address_type_name' => 'Home',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted a contact address for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function note_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'note_created',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Wrote a note for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'note_created',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Wrote a note for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function note_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'note_updated',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the note for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'note_updated',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the note for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function note_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'note_destroyed',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the note for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'note_destroyed',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the note for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function user_invited(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'user_invited',
            'objects' => json_encode([
                'user_email' => 'admin@admin.com',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Invited admin@admin.com to the account',
            $sentence
        );
    }

    /** @test */
    public function contact_template_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_template_updated',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the template used to display the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_template_updated',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the template used to display the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_date_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_date_created',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'label' => 'birthdate',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Added a date named birthdate for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_date_created',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'label' => 'birthdate',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Added a date named birthdate for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_date_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_date_updated',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'label' => 'birthdate',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated a date named birthdate for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_date_updated',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'label' => 'birthdate',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated a date named birthdate for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_date_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_date_destroyed',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted a date for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_date_destroyed',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted a date for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_reminder_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_reminder_created',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'reminder_name' => 'birthdate',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the reminder called birthdate for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_reminder_created',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'reminder_name' => 'birthdate',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Created the reminder called birthdate for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_reminder_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_reminder_updated',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'reminder_name' => 'birthdate',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the reminder called birthdate for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_reminder_updated',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'reminder_name' => 'birthdate',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the reminder called birthdate for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function contact_reminder_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_reminder_destroyed',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted a reminder for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'contact_reminder_destroyed',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted a reminder for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function user_notification_channel_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'user_notification_channel_created',
            'objects' => json_encode([
                'label' => 'mon email',
                'type' => 'email',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Added a new notification channel called mon email of the type email',
            $sentence
        );
    }

    /** @test */
    public function user_notification_channel_toggled(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'user_notification_channel_toggled',
            'objects' => json_encode([
                'label' => 'mon email',
                'type' => 'email',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Toggled the notification channel called mon email of the type email',
            $sentence
        );
    }

    /** @test */
    public function user_notification_channel_verified(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'user_notification_channel_verified',
            'objects' => json_encode([
                'label' => 'mon email',
                'type' => 'email',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Verified the notification channel called mon email of the type email',
            $sentence
        );
    }

    /** @test */
    public function user_notification_channel_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'user_notification_channel_destroyed',
            'objects' => json_encode([
                'label' => 'mon email',
                'type' => 'email',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the notification channel called mon email of the type email',
            $sentence
        );
    }

    /** @test */
    public function loan_created(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'loan_created',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'loan_name' => 'toy',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Added a loan called toy for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'loan_created',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'loan_name' => 'toy',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Added a loan called toy for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function loan_updated(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'loan_updated',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'loan_name' => 'toy',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the loan called toy for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'loan_updated',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'loan_name' => 'toy',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Updated the loan called toy for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }

    /** @test */
    public function loan_destroyed(): void
    {
        $log = AuditLog::factory()->create([
            'action_name' => 'loan_destroyed',
            'objects' => json_encode([
                'contact_id' => 123,
                'contact_name' => 'Monica Geller',
                'loan_name' => 'toy',
            ]),
        ]);

        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the loan called toy for the contact Monica Geller (deleted)',
            $sentence
        );

        $contact = Contact::factory()->create();
        $log = AuditLog::factory()->create([
            'action_name' => 'loan_destroyed',
            'objects' => json_encode([
                'contact_id' => $contact->id,
                'contact_name' => $contact->getName($loggedUser),
                'loan_name' => 'toy',
            ]),
        ]);

        $url = env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id;
        $loggedUser = User::factory()->create();
        $sentence = AuditLogHelper::process($log, $loggedUser);
        $this->assertEquals(
            'Deleted the loan called toy for the contact <a href="'.$url.'">'.$contact->getName($loggedUser).'</a>',
            $sentence
        );
    }
}
