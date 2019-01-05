<?php

namespace Tests\Unit\Services\Contact\Relationship;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Relationship\DestroyRelationship;

class DestroyRelationshipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_relationship()
    {
        $contactA = factory(Contact::class)->create([]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $contactA->account_id,
        ]);

        $relationship = factory(Relationship::class)->create([
            'account_id' => $contactA->account_id,
            'contact_is' => $contactA,
            'of_contact' => $contactB,
        ]);

        $request = [
            'account_id' => $contactA->account_id,
            'relationship_id' => $relationship->id,
        ];

        $relationshipService = new DestroyRelationship;
        $relationshipService->execute($request);

        $this->assertDatabaseMissing('relationships', [
            'id' => $relationship->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
        ];

        $this->expectException(ValidationException::class);

        (new DestroyRelationship)->execute($request);
    }

    public function test_it_throws_an_exception_if_relationship_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $relationship = factory(Relationship::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'relationship_id' => $relationship->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        (new DestroyRelationship)->execute($request);
    }
}
