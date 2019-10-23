<?php

namespace Tests\Unit\Services\Account;

use Tests\TestCase;
use App\Models\User\User;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Settings\ExportAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExportAccountTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_exports_account_information()
    {
        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $user->account->id,
            'user_id' => $user->id,
        ];

        app(ExportAccount::class)->execute($request);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [];

        $this->expectException(ValidationException::class);
        app(ExportAccount::class)->execute($request);
    }
}
