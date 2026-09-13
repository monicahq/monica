<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetailListTest extends TestCase
{
    #[Test]
    public function it_pairs_a_label_with_a_value(): void
    {
        $this->blade('<x-detail-list><x-detail-list.row label="Template">Dwight</x-detail-list.row></x-detail-list>')
            ->assertSee('Template')
            ->assertSee('Dwight');
    }
}
