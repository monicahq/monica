<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\Administrator;
use App\Models\User;
use App\ViewModels\Admin\AdministratorsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministratorsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_everybody_holding_the_capacity(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $dwight = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        Administrator::factory()->create(['user_id' => $michael->id]);
        Administrator::factory()->create(['user_id' => $dwight->id]);
        User::factory()->create(['first_name' => 'Jim', 'last_name' => 'Halpert']);

        $administrators = new AdministratorsViewModel()->administrators();

        $this->assertCount(2, $administrators);
        $this->assertEquals(2, new AdministratorsViewModel()->count());
    }

    #[Test]
    public function it_says_who_granted_it_and_when(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $dwight = User::factory()->create();
        Administrator::factory()->grantedBy($michael)->create(['user_id' => $dwight->id]);

        $administrator = new AdministratorsViewModel()->administrators()->first();

        $this->assertEquals('Michael Scott', $administrator->grantedBy->name);
        $this->assertNotNull($administrator->created_at);
    }

    #[Test]
    public function it_leaves_the_console_unnamed_since_nobody_granted_it(): void
    {
        Administrator::factory()->create();

        $administrator = new AdministratorsViewModel()->administrators()->first();

        $this->assertNull($administrator->grantedBy);
    }

    #[Test]
    public function it_keeps_somebody_who_cannot_sign_in_in_the_list_so_the_state_is_plain(): void
    {
        $dwight = User::factory()->create(['is_active' => false]);
        Administrator::factory()->create(['user_id' => $dwight->id]);

        $administrator = new AdministratorsViewModel()->administrators()->first();

        $this->assertFalse($administrator->user->is_active);
        $this->assertFalse($administrator->user->isAdministrator());
    }

    #[Test]
    public function it_knows_when_there_is_only_one_left(): void
    {
        Administrator::factory()->create();

        $this->assertTrue(new AdministratorsViewModel()->isTheLastOne());

        Administrator::factory()->create();

        $this->assertFalse(new AdministratorsViewModel()->isTheLastOne());
    }

    #[Test]
    public function it_hands_back_one_page_at_a_time(): void
    {
        Administrator::factory()->count(26)->create();

        $administrators = new AdministratorsViewModel()->administrators();

        $this->assertCount(25, $administrators);
        $this->assertTrue($administrators->hasMorePages());
    }
}
