<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AdministratorActionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministratorActionEnumTest extends TestCase
{
    #[Test]
    public function every_action_reads_as_a_sentence(): void
    {
        foreach (AdministratorActionEnum::cases() as $action) {
            $this->assertNotEmpty($action->description());
        }
    }

    #[Test]
    public function the_sentence_is_the_string_to_translate(): void
    {
        $action = AdministratorActionEnum::AdministratorAccessGranted;

        $this->assertEquals([$action->description()], $action->translationKeys());
    }
}
