<?php

namespace Tests\Unit\Domains\Settings\ManagePronouns\Services;

use App\Domains\Settings\ManagePronouns\Services\UpdatePronoun;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Pronoun;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdatePronounTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_pronoun(): void
    {
        $ross = $this->createAdministrator();
        $pronoun = Pronoun::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $pronoun);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdatePronoun)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $pronoun = Pronoun::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $pronoun);
    }

    /** @test */
    public function it_fails_if_pronoun_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $pronoun = Pronoun::factory()->create();
        $this->executeService($ross, $ross->account, $pronoun);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $pronoun = Pronoun::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $pronoun);
    }

    private function executeService(User $author, Account $account, Pronoun $pronoun): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'pronoun_id' => $pronoun->id,
            'name' => 'pronoun name',
        ];

        $pronoun = (new UpdatePronoun)->execute($request);

        $this->assertDatabaseHas('pronouns', [
            'id' => $pronoun->id,
            'account_id' => $account->id,
            'name' => 'pronoun name',
        ]);
    }
}
