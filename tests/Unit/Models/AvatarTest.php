<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Avatar;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AvatarTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $avatar = Avatar::factory()->create();

        $this->assertTrue($avatar->contact()->exists());
    }
}
