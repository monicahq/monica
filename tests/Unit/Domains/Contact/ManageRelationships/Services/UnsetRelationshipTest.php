<?php

namespace Tests\Unit\Domains\Contact\ManageRelationships\Services;

use App\Domains\Contact\ManageRelationships\Services\UnsetRelationship;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UnsetRelationshipTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_unsets_relationship(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $groupType = RelationshipGroupType::factory()->create(['account_id' => $regis->account->id]);
        $type = RelationshipType::factory()->create(['relationship_group_type_id' => $groupType->id]);
        RelationshipType::factory()->create([
            'relationship_group_type_id' => $groupType->id,
            'name' => $type->name_reverse_relationship,
        ]);
        $contact->relationships()->syncWithoutDetaching([
            $otherContact->id => [
                'relationship_type_id' => $type->id,
            ],
        ]);

        $this->executeService($regis, $regis->account, $type, $vault, $contact, $otherContact);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UnsetRelationship)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $account = Account::factory()->create();
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $groupType = RelationshipGroupType::factory()->create(['account_id' => $regis->account->id]);
        $type = RelationshipType::factory()->create(['relationship_group_type_id' => $groupType->id]);
        RelationshipType::factory()->create([
            'relationship_group_type_id' => $groupType->id,
            'name' => $type->name_reverse_relationship,
        ]);
        $contact->relationships()->syncWithoutDetaching([
            $otherContact->id => [
                'relationship_type_id' => $type->id,
            ],
        ]);

        $this->executeService($regis, $account, $type, $vault, $contact, $otherContact);
    }

    /** @test */
    public function it_fails_if_relationship_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = $this->createAccount();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $groupType = RelationshipGroupType::factory()->create();
        $type = RelationshipType::factory()->create(['relationship_group_type_id' => $groupType->id]);
        RelationshipType::factory()->create([
            'relationship_group_type_id' => $groupType->id,
            'name' => $type->name_reverse_relationship,
        ]);
        $contact->relationships()->syncWithoutDetaching([
            $otherContact->id => [
                'relationship_type_id' => $type->id,
            ],
        ]);

        $this->executeService($regis, $account, $type, $vault, $contact, $otherContact);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $contact = Contact::factory()->create();
        $otherContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $groupType = RelationshipGroupType::factory()->create(['account_id' => $regis->account->id]);
        $type = RelationshipType::factory()->create(['relationship_group_type_id' => $groupType->id]);
        RelationshipType::factory()->create([
            'relationship_group_type_id' => $groupType->id,
            'name' => $type->name_reverse_relationship,
        ]);
        $contact->relationships()->syncWithoutDetaching([
            $otherContact->id => [
                'relationship_type_id' => $type->id,
            ],
        ]);

        $this->executeService($regis, $regis->account, $type, $vault, $contact, $otherContact);
    }

    /** @test */
    public function it_fails_if_other_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherContact = Contact::factory()->create();
        $groupType = RelationshipGroupType::factory()->create(['account_id' => $regis->account->id]);
        $type = RelationshipType::factory()->create(['relationship_group_type_id' => $groupType->id]);
        RelationshipType::factory()->create([
            'relationship_group_type_id' => $groupType->id,
            'name' => $type->name_reverse_relationship,
        ]);
        $contact->relationships()->syncWithoutDetaching([
            $otherContact->id => [
                'relationship_type_id' => $type->id,
            ],
        ]);

        $this->executeService($regis, $regis->account, $type, $vault, $contact, $otherContact);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherContact = Contact::factory()->create(['vault_id' => $vault->id]);
        $groupType = RelationshipGroupType::factory()->create(['account_id' => $regis->account->id]);
        $type = RelationshipType::factory()->create(['relationship_group_type_id' => $groupType->id]);
        RelationshipType::factory()->create([
            'relationship_group_type_id' => $groupType->id,
            'name' => $type->name_reverse_relationship,
        ]);
        $contact->relationships()->syncWithoutDetaching([
            $otherContact->id => [
                'relationship_type_id' => $type->id,
            ],
        ]);

        $this->executeService($regis, $regis->account, $type, $vault, $contact, $otherContact);
    }

    private function executeService(
        User $author,
        Account $account,
        RelationshipType $type,
        Vault $vault,
        Contact $contact,
        Contact $otherContact
    ): void {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'relationship_type_id' => $type->id,
            'contact_id' => $contact->id,
            'other_contact_id' => $otherContact->id,
        ];

        (new UnsetRelationship)->execute($request);

        $this->assertDatabaseMissing('relationships', [
            'relationship_type_id' => $type->id,
            'contact_id' => $contact->id,
            'related_contact_id' => $otherContact->id,
        ]);
    }
}
