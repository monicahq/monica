<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Models\User;
use App\Helpers\MonetaryNumberHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MonetaryNumberHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_number_according_to_the_user_preference(): void
    {
        $number = 1234567;
        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL,
        ]);
        $this->assertEquals(
            '12,345.67',
            MonetaryNumberHelper::format($user, $number)
        );

        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL,
        ]);
        $this->assertEquals(
            '12 345,67',
            MonetaryNumberHelper::format($user, $number)
        );

        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_NO_SPACE_DOT_DECIMAL,
        ]);
        $this->assertEquals(
            '12345.67',
            MonetaryNumberHelper::format($user, $number)
        );
    }
}
