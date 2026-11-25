<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StepsTest extends TestCase
{
    #[Test]
    public function it_marks_the_current_step_and_says_which_ones_are_done(): void
    {
        $view = $this->blade('<x-steps :steps="[\'Select animals\', \'Preview\', \'Result\']" :current="2" />');

        $view->assertSeeInOrder(['Select animals', '(done)', 'aria-current="step"', 'Preview', 'Result'], false);
        $view->assertSee('aria-label="Steps"', false);
    }
}
