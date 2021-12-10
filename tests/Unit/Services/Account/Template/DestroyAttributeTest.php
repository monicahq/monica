<?php

namespace Tests\Unit\Services\Account\Template;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Attribute;
use App\Models\Information;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Template\DestroyAttribute;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyAttributeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_an_attribute(): void
    {
        $ross = $this->createAdministrator();
        $attribute = $this->createAttributeLinkedToAccount($ross->account);
        $this->executeService($ross, $ross->account, $attribute);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyAttribute)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $attribute = $this->createAttributeLinkedToAccount($ross->account);
        $this->executeService($ross, $account, $attribute);
    }

    /** @test */
    public function it_fails_if_attribute_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $attribute = $this->createAttributeLinkedToAccount($account);
        $this->executeService($ross, $ross->account, $attribute);
    }

    private function executeService(User $author, Account $account, Attribute $attribute): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'attribute_id' => $attribute->id,
        ];

        (new \App\Services\Account\Template\DestroyAttribute)->execute($request);

        $this->assertDatabaseMissing('attributes', [
            'id' => $attribute->id,
        ]);
    }

    private function createAttributeLinkedToAccount(Account $account): Attribute
    {
        $information = Information::factory()->create([
            'account_id' => $account->id,
        ]);
        $attribute = Attribute::factory()->create([
            'information_id' => $information->id,
        ]);

        return $attribute;
    }
}
