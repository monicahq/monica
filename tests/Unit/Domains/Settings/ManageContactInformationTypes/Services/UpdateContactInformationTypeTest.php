<?php

namespace Tests\Unit\Domains\Settings\ManageContactInformationTypes\Services;

use App\Domains\Settings\ManageContactInformationTypes\Services\UpdateContactInformationType;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\ContactInformationType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateContactInformationTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_type(): void
    {
        $ross = $this->createAdministrator();
        $type = ContactInformationType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $type);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateContactInformationType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $type = ContactInformationType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $type);
    }

    /** @test */
    public function it_fails_if_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $type = ContactInformationType::factory()->create();
        $this->executeService($ross, $ross->account, $type);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $type = ContactInformationType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $type);
    }

    private function executeService(User $author, Account $account, ContactInformationType $type): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'contact_information_type_id' => $type->id,
            'name' => 'type name',
            'type' => $type->type,
        ];

        $type = (new UpdateContactInformationType)->execute($request);

        $this->assertDatabaseHas('contact_information_types', [
            'id' => $type->id,
            'account_id' => $account->id,
            'name' => 'type name',
        ]);
    }
}
