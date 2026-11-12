<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\DefaultFieldEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DefaultFieldEnumTest extends TestCase
{
    #[Test]
    public function it_names_a_list_for_every_field_whose_type_asks_for_one(): void
    {
        foreach (DefaultFieldEnum::cases() as $field) {
            if (! $field->type()->usesValueList() && ! $field->type()->usesALabelList()) {
                continue;
            }

            $this->assertNotNull($field->valueList());
        }
    }
}
