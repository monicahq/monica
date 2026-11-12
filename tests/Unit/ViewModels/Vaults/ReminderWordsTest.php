<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\ViewModels\Vaults\ReminderWords;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderWordsTest extends TestCase
{
    #[Test]
    public function it_says_nothing_for_a_rhythm_of_none(): void
    {
        $this->assertSame('', ReminderWords::cadence(ReminderCadenceEnum::Yearly, 0));
    }
}
