<?php

namespace Tests\Unit\Domains\Settings\CancelAccount\Web\ViewHelpers;

use App\Domains\Settings\CancelAccount\Web\ViewHelpers\CancelAccountViewHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
