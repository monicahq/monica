<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\SupportRequest;
use App\Models\User;
use App\ViewModels\Admin\SupportRequestContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestContextTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_describes_the_account_behind_the_request_without_reading_its_vaults(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott', 'email' => 'michael@scottpaper.com']);
        $supportRequest = SupportRequest::factory()->create([
            'account_id' => $michael->account_id,
            'user_id' => $michael->id,
            'application_version' => '5.0.0-beta.4',
            'locale' => 'en',
        ]);

        $context = new SupportRequestContext($supportRequest);

        $this->assertEquals('Michael Scott', $context->name());
        $this->assertEquals('michael@scottpaper.com', $context->email());
        $this->assertEquals(
            ['Account', 'Access', 'Language', 'Version', 'Content', 'Signed up'],
            array_column($context->facts(), 'label'),
        );
        $this->assertEquals('5.0.0-beta.4', $context->facts()[3]['value']);
    }

    #[Test]
    public function it_still_reads_once_the_user_is_gone(): void
    {
        $supportRequest = SupportRequest::factory()->create(['user_id' => null]);

        $this->assertEquals('Deleted user', new SupportRequestContext($supportRequest)->name());
    }
}
