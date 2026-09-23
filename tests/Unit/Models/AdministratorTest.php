<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Administrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministratorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_was_granted_by_nobody_when_the_console_designated_them(): void
    {
        $capacity = Administrator::factory()->create();

        $this->assertNull($capacity->granted_by_user_id);
        $this->assertFalse($capacity->grantedBy()->exists());
    }
}
