<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NameFlagInContextTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_a_flag_on_for_the_account_once_however_often_it_is_asked(): void
    {
        $jim = User::factory()->create();

        $jim->account->features()->active('vcard-export');
        $jim->account->features()->active('vcard-export');
        $jim->account->features()->active('vcard-export');

        $this->assertSame(['vcard-export'], Context::get('flags'));
    }

    #[Test]
    public function it_names_neither_a_flag_off_for_the_account_nor_one_asked_for_nobody(): void
    {
        $dwight = User::factory()->create();

        $dwight->account->features()->active('marketplace');
        Feature::for(null)->active('vcard-export');

        $this->assertFalse(Context::has('flags'));
    }

    #[Test]
    public function it_leaves_the_flags_out_of_a_queued_job_while_the_request_keeps_them(): void
    {
        $jim = User::factory()->create();
        $jim->account->features()->active('vcard-export');

        $payload = Context::dehydrate();

        $this->assertArrayNotHasKey('flags', $payload['data'] ?? []);
        $this->assertSame(['vcard-export'], Context::get('flags'));
    }
}
