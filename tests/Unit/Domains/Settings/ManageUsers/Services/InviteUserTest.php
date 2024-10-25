<?php

namespace Tests\Unit\Domains\Settings\ManageUsers\Services;

use App\Domains\Settings\ManageUsers\Services\InviteUser;
use App\Exceptions\NotEnoughPermissionException;
use App\Mail\UserInvited;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InviteUserTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_invites_another_user(): void
    {
        $author = $this->createAdministrator();
        $this->executeService($author->account, $author);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $author = $this->createAdministrator();
        $account = Account::factory()->create();
        $this->expectException(ModelNotFoundException::class);
        $this->executeService($account, $author);
    }

    /** @test */
    public function it_fails_if_email_is_already_taken(): void
    {
        $author = $this->createAdministrator();
        User::factory()->create([
            'email' => 'admin@admin.com',
        ]);
        $this->expectException(ValidationException::class);
        $this->executeService($author->account, $author);
    }

    /** @test */
    public function it_fails_if_user_is_not_account_administrator(): void
    {
        $author = $this->createUser();

        $this->expectException(NotEnoughPermissionException::class);
        $this->executeService($author->account, $author);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new InviteUser)->execute($request);
    }

    private function executeService(Account $account, User $author): void
    {
        Mail::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'email' => 'admin@admin.com',
            'is_administrator' => true,
        ];

        $newUser = (new InviteUser)->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $newUser->id,
            'email' => $newUser->email,
            'is_account_administrator' => true,
        ]);

        Mail::assertQueued(UserInvited::class, function ($mail) use ($newUser) {
            return $mail->hasTo($newUser->email);
        });
    }
}
