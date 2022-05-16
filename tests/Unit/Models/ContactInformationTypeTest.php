<?php

namespace Tests\Unit\Models;

use App\Models\ContactInformationType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
