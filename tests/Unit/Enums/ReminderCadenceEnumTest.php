<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ReminderCadenceEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderCadenceEnumTest extends TestCase
{
    #[Test]
    public function it_knows_which_cadences_come_round_again(): void
    {
        $this->assertFalse(ReminderCadenceEnum::Once->recurs());
        $this->assertTrue(ReminderCadenceEnum::Weekly->recurs());
        $this->assertTrue(ReminderCadenceEnum::Monthly->recurs());
        $this->assertTrue(ReminderCadenceEnum::Yearly->recurs());
    }
}
