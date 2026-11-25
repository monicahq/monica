<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\TrashItemKindEnum;
use App\Models\GroupNote;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrashItemKindEnumTest extends TestCase
{
    #[Test]
    public function it_finds_the_kind_of_a_record(): void
    {
        $this->assertSame(TrashItemKindEnum::GroupNote, TrashItemKindEnum::fromModel(new GroupNote));
    }

    #[Test]
    public function it_counts_items_in_a_phrase_that_varies_with_the_number(): void
    {
        $this->assertSame('1 person', TrashItemKindEnum::Contact->countPhrase(1));
        $this->assertSame('3 life events', TrashItemKindEnum::LifeEvent->countPhrase(3));
        $this->assertSame('2 people, 3 life events and 1 file', TrashItemKindEnum::countsPhrase(['group-file' => 1, 'contact' => 2, 'life-event' => 3]));
    }
}
