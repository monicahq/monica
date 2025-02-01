<?php

namespace Tests\Unit\Domains\Settings\ManagePronouns\Services;

use App\Domains\Settings\ManagePronouns\Services\CreatePronoun;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Pronoun;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreatePronounTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_pronoun(): void
    {
        $ross = $this->createAdministrator();
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreatePronoun)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $this->executeService($ross, $ross->account);
    }

    private function executeService(User $author, Account $account): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'name' => 'pronoun name',
        ];

        $pronoun = (new CreatePronoun)->execute($request);

        $this->assertDatabaseHas('pronouns', [
            'id' => $pronoun->id,
            'account_id' => $account->id,
            'name' => 'pronoun name',
        ]);

        $this->assertInstanceOf(
            Pronoun::class,
            $pronoun
        );
    }
}
