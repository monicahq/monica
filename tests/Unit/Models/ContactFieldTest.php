<?php

namespace Tests\Unit\Models;

use App\Models\Contact\ContactField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactFieldTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_fetches_data_field()
    {
        $contactField = new ContactField;
        $contactField->data = 'this is a test';

        $this->assertEquals(
            'this is a test',
            $contactField->data
        );
    }
}
