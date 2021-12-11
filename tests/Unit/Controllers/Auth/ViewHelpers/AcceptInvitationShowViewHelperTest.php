<?php

namespace Tests\Unit\Controllers\Auth\ViewHelpers;

use function env;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Auth\ViewHelpers\AcceptInvitationShowViewHelper;

class AcceptInvitationShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $array = AcceptInvitationShowViewHelper::data('code');
        $this->assertEquals(
            [
                'invitation_code' => 'code',
                'url' => [
                    'store' => env('APP_URL').'/invitation',
                ],
            ],
            $array
        );
    }
}
