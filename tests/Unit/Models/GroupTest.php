<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Group;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $group = Group::factory()->create();

        $this->assertTrue($group->vault()->exists());
    }

    /** @test */
    public function it_has_one_group_type()
    {
        $group = Group::factory()->create();

        $this->assertTrue($group->groupType()->exists());
    }

    /** @test */
    public function it_has_many_contacts(): void
    {
        $ross = Contact::factory()->create([]);
        $group = Group::factory()->create();

        $group->contacts()->sync([$ross->id]);

        $this->assertTrue($group->contacts()->exists());
    }
}
