<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\AvatarHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AvatarHelperTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_a_unique_uuid_for_adorable_url()
    {
        $this->assertEquals(
            36,
            strlen(AvatarHelper::generateAdorableUUID())
        );

        $this->assertInternalType(
            'string',
            AvatarHelper::generateAdorableUUID()
        );
    }
}
