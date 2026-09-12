<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use App\Http\Dav\PrincipalBackend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;
use Tests\TestCase;

class PrincipalBackendTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_serves_the_principal_of_the_person_it_was_built_for(): void
    {
        $user = User::factory()->create();
        $backend = new PrincipalBackend(user: $user);

        $this->assertEquals([
            'uri' => 'principals/'.$user->email,
            '{DAV:}displayname' => $user->name,
            '{http://sabredav.org/ns}email-address' => $user->email,
        ], $backend->getPrincipalByPath('principals/'.$user->email));
    }

    #[Test]
    public function it_serves_nobody_else(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $backend = new PrincipalBackend(user: $user);

        $this->assertNull($backend->getPrincipalByPath('principals/'.$stranger->email));
    }

    #[Test]
    public function it_holds_one_principal_under_the_collection(): void
    {
        $user = User::factory()->create();
        $backend = new PrincipalBackend(user: $user);

        $this->assertCount(1, $backend->getPrincipalsByPrefix('principals'));
        $this->assertEmpty($backend->getPrincipalsByPrefix('somewhere-else'));
    }

    #[Test]
    public function it_finds_the_principal_by_the_email_address(): void
    {
        $user = User::factory()->create();
        $backend = new PrincipalBackend(user: $user);

        $this->assertEquals('principals/'.$user->email, $backend->findByUri('mailto:'.$user->email, 'principals'));
        $this->assertNull($backend->findByUri('mailto:someone@example.test', 'principals'));
    }

    #[Test]
    public function it_writes_nothing_a_device_asks_it_to(): void
    {
        $user = User::factory()->create();
        $backend = new PrincipalBackend(user: $user);
        $propPatch = new PropPatch(['{DAV:}displayname' => 'Assistant Regional Manager']);

        $backend->updatePrincipal('principals/'.$user->email, $propPatch);

        $this->assertFalse($propPatch->commit());
        $this->assertEquals($user->name, $user->refresh()->name);
    }

    #[Test]
    public function it_knows_nothing_of_groups(): void
    {
        $user = User::factory()->create();
        $backend = new PrincipalBackend(user: $user);

        $this->assertEmpty($backend->getGroupMemberSet('principals/'.$user->email));
        $this->assertEmpty($backend->getGroupMembership('principals/'.$user->email));
        $this->assertEmpty($backend->searchPrincipals('principals', ['{DAV:}displayname' => 'Dwight']));

        $this->expectException(Forbidden::class);

        $backend->setGroupMemberSet('principals/'.$user->email, []);
    }
}
