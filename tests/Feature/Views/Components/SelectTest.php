<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SelectTest extends TestCase
{
    #[Test]
    public function its_name_follows_its_id_unless_it_is_given_one(): void
    {
        $this->blade('<x-select id="branch"><option value="x">X</option></x-select>')
            ->assertSee('name="branch"', escape: false);

        $this->blade('<x-select id="branch-2" name="branch"><option value="x">X</option></x-select>')
            ->assertSee('name="branch"', escape: false);
    }
}
