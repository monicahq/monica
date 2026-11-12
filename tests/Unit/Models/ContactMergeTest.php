<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactMergeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_still_reaches_the_absorbed_contact_once_it_is_soft_deleted(): void
    {
        $merge = ContactMerge::factory()->create();
        $merge->mergedContact->delete();

        $this->assertInstanceOf(Contact::class, $merge->fresh()->mergedContact);
    }

    #[Test]
    public function it_stands_until_it_is_undone(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(ContactMerge::factory()->create()->stands());
        $this->assertFalse(ContactMerge::factory()->undoneBy($user)->create()->stands());
    }
}
