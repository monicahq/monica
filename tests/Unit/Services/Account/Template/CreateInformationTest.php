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

class CreateInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_an_information(): void
    {
        $ross = $this->createAdministrator();
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Gender',
        ];

        $this->expectException(ValidationException::class);
        (new \App\Services\Account\Template\CreateInformation)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    private function executeService(User $author, Account $account): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'name' => 'Gender',
            'allows_multiple_entries' => true,
        ];

        $information = (new \App\Services\Account\Template\CreateInformation)->execute($request);

        $this->assertDatabaseHas('information', [
            'id' => $information->id,
            'account_id' => $account->id,
            'name' => 'Gender',
            'allows_multiple_entries' => true,
        ]);

        $this->assertInstanceOf(
            Information::class,
            $information
        );
    }
}
