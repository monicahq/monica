<?php

namespace Tests\Unit\Rules;

use Tests\TestCase;
use App\Rules\StrongPassword;

class StrongPasswordTest extends TestCase
{
    /** @test */
    public function it_passes_for_strong_password_with_user_data()
    {
        $rule = new StrongPassword([
            'John',
            'Smith',
            'john.smith@gmail.com',
        ]);

        $this->assertTrue(
            $rule->passes('password', 'correct horse battery staple')
        );
    }

    /** @test */
    public function it_passes_for_strong_password_without_user_data()
    {
        $rule = new StrongPassword;

        $this->assertTrue(
            $rule->passes('password', 'correct horse battery staple')
        );
    }

    /** @test */
    public function it_fails_for_weak_password_with_user_data()
    {
        $rule = new StrongPassword([
            'John',
            'Smith',
            'john.smith@gmail.com',
        ]);

        $this->assertFalse(
            $rule->passes('password', 'johnsmith')
        );
    }

    /** @test */
    public function it_fails_for_weak_password_without_user_data()
    {
        $rule = new StrongPassword;

        $this->assertFalse(
            $rule->passes('password', 'password&1')
        );
    }

    /** @test */
    public function it_allows_strength_threshold_to_be_customised()
    {
        config()->set('zxcvbn.password_strength_threshold', 0);

        $rule = new StrongPassword;

        $this->assertTrue(
            $rule->passes('password', 'password&1')
        );
    }

    /** @test */
    public function it_gives_a_message_that_describes_issue()
    {
        $rule = new StrongPassword;

        $rule->passes('password', 'password&1');

        $this->assertEquals(
            'The password chosen is insecure as it is similar to commonly used passwords.',
            $rule->message()
        );
    }
}
