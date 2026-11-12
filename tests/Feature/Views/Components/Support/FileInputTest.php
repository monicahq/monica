<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Support;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileInputTest extends TestCase
{
    #[Test]
    public function it_sends_several_files_and_leaves_out_on_the_spot_the_ones_that_cannot_be_sent(): void
    {
        $rendered = $this->blade('<x-support.file-input id="files" accept=".png,.pdf" :maximum-files="5" :maximum-kilobytes="10240" />');

        $rendered->assertSee('name="files[]"', escape: false);
        $rendered->assertSee('multiple', escape: false);
        $rendered->assertSee('window.keepAcceptedFiles($event.target, 10240, 5)', escape: false);
        $rendered->assertSee('role="status"', escape: false);
        $rendered->assertSee('Up to 5 files of 10 MB each: screenshots, PDF or text.');
    }
}
