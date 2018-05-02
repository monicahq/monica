<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CustomFieldPattern extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $customFieldPattern = factory('App\CustomFieldPattern')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($customFieldPattern->account()->exists());
    }

    public function test_it_has_many_custom_fields()
    {
        $customFieldPattern = factory('App\CustomFieldPattern')->create([]);
        $customField = factory('App\CustomField', 2)->create([
            'custom_field_id' => $customFieldPattern->id,
        ]);

        $this->assertTrue($customFieldPattern->fields()->exists());
    }

    public function test_it_retrieves_the_name_of_the_custom_field()
    {
        $customFieldPattern = factory('App\CustomFieldPattern')->make([
            'name' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $customFieldPattern->name
        );
    }

    public function test_it_retrieves_the_icon_name()
    {
        $customFieldPattern = factory('App\CustomFieldPattern')->make([
            'icon_name' => 'telephone',
        ]);

        $this->assertEquals(
            'telephone',
            $customFieldPattern->icon_name
        );
    }
}
