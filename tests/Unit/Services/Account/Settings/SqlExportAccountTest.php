<?php

namespace Tests\Unit\Services\Account\Settings;

use Tests\TestCase;
use App\Models\User\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Settings\SqlExportAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SqlExportAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_exports_account_information()
    {
        Storage::fake('local');

        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ];

        $filename = app(SqlExportAccount::class)->execute($request);

        $this->assertStringStartsWith('temp/', $filename);
        $this->assertStringEndsWith('.sql', $filename);
        Storage::disk('local')->assertExists($filename);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [];

        $this->expectException(ValidationException::class);
        app(SqlExportAccount::class)->execute($request);
    }
}
