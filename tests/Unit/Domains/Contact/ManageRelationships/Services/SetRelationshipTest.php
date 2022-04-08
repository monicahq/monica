<?php

namespace Tests\Unit\Domains\Contact\ManageRelationships\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vault;
use App\Models\Account;
use App\Models\Contact;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use App\Models\RelationshipType;
use App\Models\RelationshipGroupType;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Exceptions\NotEnoughPermissionException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Contact\ManageRelationships\Services\SetRelationship;

class SetRelationshipTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sets_relationship(): void
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

        $this->executeService($regis, $regis->account, $type, $vault, $contact, $otherContact);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new SetRelationship)->execute($request);
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
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'relationship_type_id' => $type->id,
            'contact_id' => $contact->id,
            'other_contact_id' => $otherContact->id,
        ];

        (new SetRelationship)->execute($request);

        $this->assertDatabaseHas('relationships', [
            'relationship_type_id' => $type->id,
            'contact_id' => $contact->id,
            'related_contact_id' => $otherContact->id,
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'relationship_set';
        });

        Queue::assertPushed(CreateContactLog::class, function ($job) {
            return $job->contactLog['action_name'] === 'relationship_set';
        });
    }
}
