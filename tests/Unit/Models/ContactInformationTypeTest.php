<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ContactInformationType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactInformationTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $type = ContactInformationType::factory()->create();

        $this->assertTrue($type->account()->exists());
    }
}
