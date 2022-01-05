<?php

namespace Tests\Unit\Services\Account\Template;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Information;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\ManageTemplate\DestroyInformation;

class DestroyInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_an_information(): void
    {
        $ross = $this->createAdministrator();
        $information = Information::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $information);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyInformation)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $information = Information::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $information);
    }

    /** @test */
    public function it_fails_if_information_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $information = Information::factory()->create();
        $this->executeService($ross, $ross->account, $information);
    }

    private function executeService(User $author, Account $account, Information $information): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'information_id' => $information->id,
        ];

        (new DestroyInformation)->execute($request);

        $this->assertDatabaseMissing('information', [
            'id' => $information->id,
        ]);
    }
}
