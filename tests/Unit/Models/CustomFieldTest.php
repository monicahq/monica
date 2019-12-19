<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Photo;
use App\Models\Account\Account;
use App\Models\Account\CustomField;
use App\Models\Account\Field;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CustomFieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $field = factory(CustomField::class)->create([]);
        $this->assertTrue($field->account()->exists());
    }

    public function test_it_has_many_companies()
    {
        $customField = factory(CustomField::class)->create([]);
        factory(Field::class, 3)->create([
            'custom_field_id' => $customField->id,
        ]);
        $this->assertTrue($customField->fields()->exists());
    }
}
