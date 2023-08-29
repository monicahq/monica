<?php

namespace Tests\Unit\Domains\Settings\ManageUsers\Web\ViewHelpers;

use App\Domains\Settings\ManageUsers\Web\ViewHelpers\UserIndexViewHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class UserIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();

        $array = UserIndexViewHelper::data($user);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'is_account_administrator' => false,
                    'invitation_code' => null,
                    'invitation_accepted_at' => null,
                    'is_logged_user' => true,
                    'url' => [
                        'show' => env('APP_URL').'/settings/users/'.$user->id,
                        'update' => env('APP_URL').'/settings/users/'.$user->id,
                        'destroy' => env('APP_URL').'/settings/users/'.$user->id,
                    ],
                ],
            ],
            $array['users']->toArray()
        );
        $this->assertEquals(
            [
                'settings' => [
                    'index' => env('APP_URL').'/settings',
                ],
                'users' => [
                    'create' => env('APP_URL').'/settings/users/create',
                ],
            ],
            $array['url']
        );
    }
}
