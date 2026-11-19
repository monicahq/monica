<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\DefaultFieldEnum;
use App\Enums\FieldTypeEnum;
use App\Helpers\OwnedFields;
use App\Models\Field;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OwnedFieldsTest extends TestCase
{
    #[Test]
    public function it_keeps_the_links_of_gift_and_outing_ideas_off_the_card(): void
    {
        foreach ([DefaultFieldEnum::GiftIdeaLink, DefaultFieldEnum::OutingIdeaLink] as $kept) {
            $field = new Field()->forceFill(['key' => $kept->value, 'type' => FieldTypeEnum::Url]);

            $this->assertNull(OwnedFields::propertyFor($field));
        }
    }

    #[Test]
    public function it_still_puts_a_website_and_an_online_profile_on_the_card(): void
    {
        foreach ([DefaultFieldEnum::Website, DefaultFieldEnum::ProfessionalProfile, DefaultFieldEnum::OnlineProfileLink] as $link) {
            $field = new Field()->forceFill(['key' => $link->value, 'type' => FieldTypeEnum::Url]);

            $this->assertSame('URL', OwnedFields::propertyFor($field));
        }
    }
}
