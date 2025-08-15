<?php

namespace Tests\Unit\Traits;

use App\Models\Group;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HasUuidsTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_generates_a_uuid(): void
    {
        $group = Group::factory()->create();

        $this->assertNotNull($group->uuid);
        $this->assertTrue(Str::isUuid($group->uuid));
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'uuid' => $group->uuid,
        ]);
    }

    #[Test]
    public function it_saves_a_uuid(): void
    {
        $group = Group::factory()->create();

        $uuid = Str::uuid7();
        $group->uuid = $uuid;
        $group->save();

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'uuid' => $uuid,
        ]);

        $this->assertEquals($group->uuid, $uuid);
    }
}
