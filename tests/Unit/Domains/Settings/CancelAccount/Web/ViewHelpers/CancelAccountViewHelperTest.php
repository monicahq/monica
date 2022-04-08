<?php

namespace Tests\Unit\Domains\Settings\CancelAccount\Web\ViewHelpers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Settings\CancelAccount\Web\ViewHelpers\CancelAccountViewHelper;

class CancelAccountViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $array = CancelAccountViewHelper::data();

        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'back' => env('APP_URL').'/settings',
                'destroy' => env('APP_URL').'/settings/cancel',
            ],
            $array['url']
        );
    }
}
