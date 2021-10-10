<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Models\User\User;
use App\Helpers\FormHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FormHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_name_order_for_a_form()
    {
        $user = factory(User::class)->create([]);
        $user->name_order = 'firstname_lastname';
        $this->assertEquals(
            'firstname',
            FormHelper::getNameOrderForForms($user)
        );

        $user->name_order = 'firstname_lastname_nickname';
        $this->assertEquals(
            'firstname',
            FormHelper::getNameOrderForForms($user)
        );

        $user->name_order = 'firstname_nickname_lastname';
        $this->assertEquals(
            'firstname',
            FormHelper::getNameOrderForForms($user)
        );

        $user->name_order = 'lastname_firstname';
        $this->assertEquals(
            'lastname',
            FormHelper::getNameOrderForForms($user)
        );

        $user->name_order = 'lastname_firstname_nickname';
        $this->assertEquals(
            'lastname',
            FormHelper::getNameOrderForForms($user)
        );

        $user->name_order = 'lastname_nickname_firstname';
        $this->assertEquals(
            'lastname',
            FormHelper::getNameOrderForForms($user)
        );
    }
}
