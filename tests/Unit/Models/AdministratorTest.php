<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministratorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $capacity = Administrator::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($capacity->user()->exists());
        $this->assertEquals('Michael Scott', $capacity->user->name);
    }

    #[Test]
    public function it_belongs_to_the_administrator_who_granted_it(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $dwight = User::factory()->create();

        $capacity = Administrator::factory()->grantedBy($michael)->create(['user_id' => $dwight->id]);

        $this->assertTrue($capacity->grantedBy()->exists());
        $this->assertEquals('Michael Scott', $capacity->grantedBy->name);
    }

    #[Test]
    public function it_was_granted_by_nobody_when_the_console_designated_them(): void
    {
        $capacity = Administrator::factory()->create();

        $this->assertNull($capacity->granted_by_user_id);
        $this->assertFalse($capacity->grantedBy()->exists());
    }
}
