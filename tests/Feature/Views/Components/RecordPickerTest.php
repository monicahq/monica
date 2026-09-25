<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordPickerTest extends TestCase
{
    #[Test]
    public function it_sends_the_record_chosen_under_the_name_it_is_given(): void
    {
        $rendered = $this->blade(
            '<x-record-picker name="fields[7]" id="field-7" url="/choices" label="Caregiver" chosen="uuid-of-dwight" chosen-name="Dwight Schrute" />',
        );

        $rendered->assertSee('type="hidden" name="fields[7]" x-model="chosen"', escape: false);
        $rendered->assertSee('x-data="recordPicker(', escape: false);
        $rendered->assertSee('uuid-of-dwight', escape: false);
        $rendered->assertSee('Dwight Schrute', escape: false);
    }

    #[Test]
    public function it_labels_the_search_and_points_it_at_the_list_it_fills(): void
    {
        $rendered = $this->blade('<x-record-picker name="fields[7]" id="field-7" url="/choices" label="Caregiver" />');

        $rendered->assertSee('for="field-7"', escape: false);
        $rendered->assertSee('Caregiver');
        $rendered->assertSee('aria-controls="field-7-choices"', escape: false);
        $rendered->assertSee('id="field-7-choices"', escape: false);
        $rendered->assertSee('aria-live="polite"', escape: false);
        $rendered->assertSee(__('Choose another'));
    }
}
