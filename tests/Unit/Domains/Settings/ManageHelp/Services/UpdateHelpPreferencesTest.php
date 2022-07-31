<?php

namespace Tests\Unit\Domains\Settings\ManageHelp\Services;

use App\Models\User;
use App\Settings\ManageHelp\Services\UpdateHelpPreferences;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateHelpPreferencesTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sets_the_help_preference(): void
    {
        $user = User::factory()->create([]);

        $request = [
            'user_id' => $user->id,
            'visibility' => false,
        ];

        $bool = (new UpdateHelpPreferences())->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'help_shown' => false,
        ]);

        $this->assertTrue($bool);

        $request = [
            'user_id' => $user->id,
            'visibility' => true,
        ];

        $bool = (new UpdateHelpPreferences())->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'help_shown' => true,
        ]);

        $this->assertTrue($bool);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'action' => 'account_created',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateHelpPreferences())->execute($request);
    }
}
