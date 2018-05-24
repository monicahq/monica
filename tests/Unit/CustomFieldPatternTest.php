<?php

namespace Tests\Unit;

use App\Account;
use Tests\TestCase;
use App\Models\Settings\CustomFields\CustomField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Settings\CustomFields\CustomFieldPattern;

class CustomFieldPatternTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $customFieldPattern = factory(CustomFieldPattern::class)->create([]);

        $this->assertTrue($customFieldPattern->account()->exists());
    }

    public function test_it_has_many_custom_fields()
    {
        $customFieldPattern = factory(CustomFieldPattern::class)->create([]);
        $customField = factory(CustomField::class, 2)->create([
            'custom_field_pattern_id' => $customFieldPattern->id,
        ]);

        $this->assertTrue($customFieldPattern->customFields()->exists());
    }

    public function test_it_retrieves_the_name_of_the_custom_field()
    {
        $customFieldPattern = factory(CustomFieldPattern::class)->make([
            'name' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $customFieldPattern->name
        );
    }

    public function test_it_retrieves_the_icon_name()
    {
        $customFieldPattern = factory(CustomFieldPattern::class)->make([
            'icon_name' => 'telephone',
        ]);

        $this->assertEquals(
            'telephone',
            $customFieldPattern->icon_name
        );
    }
}
