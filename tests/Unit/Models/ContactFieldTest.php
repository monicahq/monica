<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\ContactField;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactFieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_fetches_data_field()
    {
        $contactField = new ContactField;
        $contactField->data = 'this is a test';

        $this->assertEquals(
            'this is a test',
            $contactField->data
        );
    }
}
