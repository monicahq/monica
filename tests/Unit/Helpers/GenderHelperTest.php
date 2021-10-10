<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\GenderHelper;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;

class GenderHelperTest extends FeatureTestCase
{
    /** @test */
    public function it_gets_all_the_gender_inputs()
    {
        $this->signIn();

        $genders = GenderHelper::getGendersInput();

        $this->assertCount(4, $genders);
        $this->assertEquals([
            'id' => '',
            'name' => 'No gender',
        ], $genders[0]);
    }

    /** @test */
    public function it_replaces_gender_with_another_gender()
    {
        $account = factory(Account::class)->create();
        $male = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);
        $female = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        factory(Contact::class, 2)->create([
            'account_id' => $account->id,
            'gender_id' => $male,
        ]);
        factory(Contact::class)->create([
            'account_id' => $account->id,
            'gender_id' => $female,
        ]);

        GenderHelper::replace($account, $male, $female);

        $this->assertEquals(
            3,
            $female->contacts->count()
        );
    }
}
