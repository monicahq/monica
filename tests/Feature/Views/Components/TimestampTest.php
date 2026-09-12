<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TimestampTest extends TestCase
{
    #[Test]
    public function it_writes_the_moment_for_a_machine_and_spells_it_out_for_a_reader(): void
    {
        $at = Carbon::parse('2026-03-05 14:30:00');

        $this->blade('<x-timestamp :at="$at">two days ago</x-timestamp>', ['at' => $at])
            ->assertSee('datetime="'.$at->toIso8601String().'"', escape: false)
            ->assertSee('title="'.$at->toDayDateTimeString().'"', escape: false)
            ->assertSee('two days ago', escape: false);
    }

    #[Test]
    public function it_carries_the_classes_it_is_given(): void
    {
        $this->blade('<x-timestamp :at="$at" class="text-xs text-muted">then</x-timestamp>', ['at' => Carbon::now()])
            ->assertSee('class="text-xs text-muted"', escape: false);
    }
}
