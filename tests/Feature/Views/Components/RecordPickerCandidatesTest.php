<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordPickerCandidatesTest extends TestCase
{
    #[Test]
    public function it_offers_each_record_as_a_button_that_says_whether_it_is_the_one_chosen(): void
    {
        $rendered = $this->blade(
            '<x-record-picker.candidates id="record-choices" :candidates="$candidates" />',
            ['candidates' => [['uuid' => 'uuid-of-angela', 'name' => 'Angela Martin']]],
        );

        $rendered->assertSee('id="record-choices" x-merge="update"', escape: false);
        $rendered->assertSee('Angela Martin');
        $rendered->assertSee("choose('uuid-of-angela', 'Angela Martin')", escape: false);
        $rendered->assertSee(":aria-pressed=\"chosen === 'uuid-of-angela'", escape: false);
    }
}
