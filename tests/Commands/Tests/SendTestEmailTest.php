<?php

namespace Tests\Commands\Tests;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;

class SendTestEmailTest extends TestCase
{
    /** @test */
    public function error_for_bad_email()
    {
        $exampleEmail = 'no.at.symbol';

        $this->artisan('monica:test-email', ['--email' => $exampleEmail])
            ->expectsOutput("Invalid email address: \"$exampleEmail\".")
            ->assertFailed()
            ->run();
    }

    /** @test */
    public function command_prompts_for_email()
    {
        $exampleEmail = 'no.at.symbol';

        $this->artisan('monica:test-email')
            ->expectsQuestion('What email address should I send the test email to?', $exampleEmail)
            ->expectsOutput("Invalid email address: \"$exampleEmail\".")
            ->assertFailed()
            ->run();
    }

    /**
     * @test
     */
    public function command_attempts_to_send_email()
    {
        $exampleEmail = 'test@example.org';

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function ($message, $closure) use ($exampleEmail) {
                $this->assertEquals(
                    "Hi $exampleEmail, you requested a test email from Monica.",
                    $message
                );

                return true;
            });

        $this->artisan('monica:test-email', ['--email' => $exampleEmail])
            ->assertSuccessful()
            ->run();
    }
}
