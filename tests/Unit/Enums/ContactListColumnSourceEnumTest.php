<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ContactListColumnSourceEnum;
use App\Enums\ContactSortEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactListColumnSourceEnumTest extends TestCase
{
    #[Test]
    public function it_shows_email_phone_and_last_spoke_until_somebody_chooses(): void
    {
        $this->assertEquals([
            ['source' => ContactListColumnSourceEnum::Email, 'isVisible' => true],
            ['source' => ContactListColumnSourceEnum::Phone, 'isVisible' => true],
            ['source' => ContactListColumnSourceEnum::LastSpoke, 'isVisible' => true],
            ['source' => ContactListColumnSourceEnum::Template, 'isVisible' => false],
        ], ContactListColumnSourceEnum::defaults());
    }

    #[Test]
    public function it_sorts_only_by_what_the_contact_itself_holds(): void
    {
        $this->assertEquals(ContactSortEnum::Nickname, ContactListColumnSourceEnum::Nickname->sort());
        $this->assertEquals(ContactSortEnum::LastSpoke, ContactListColumnSourceEnum::LastSpoke->sort());
        $this->assertEquals(ContactSortEnum::Added, ContactListColumnSourceEnum::Added->sort());
        $this->assertNull(ContactListColumnSourceEnum::Email->sort());
        $this->assertNull(ContactListColumnSourceEnum::Field->sort());
    }

    #[Test]
    public function it_leaves_template_fields_out_of_the_shared_columns(): void
    {
        $this->assertNotContains(ContactListColumnSourceEnum::Field, ContactListColumnSourceEnum::shared());
        $this->assertCount(7, ContactListColumnSourceEnum::shared());
    }
}
