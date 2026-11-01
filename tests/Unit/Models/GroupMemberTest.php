<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\GroupMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupMemberTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_current_until_the_contact_leaves(): void
    {
        $this->assertTrue(GroupMember::factory()->create()->isCurrent());
        $this->assertFalse(GroupMember::factory()->former()->create()->isCurrent());
    }
}
