<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\MessageShortener;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageShortenerTest extends TestCase
{
    #[Test]
    public function a_message_that_fits_is_left_alone(): void
    {
        $this->assertSame('Birthday, Michael Scott.', MessageShortener::to('Birthday, Michael Scott.', 140));
    }

    #[Test]
    public function a_channel_with_no_limit_carries_whatever_it_is_given(): void
    {
        $long = str_repeat('a', 5000);

        $this->assertSame($long, MessageShortener::to($long, null));
    }

    #[Test]
    public function it_reads_a_question_mark_and_an_exclamation_mark_as_endings_too(): void
    {
        $this->assertSame('Is it today? Yes!', MessageShortener::to('Is it today? Yes! And also tomorrow, and the day after that.', 30));
    }

    #[Test]
    public function it_never_cuts_in_the_middle_of_a_word(): void
    {
        $message = 'Remember the extraordinarily complicated arrangement everybody agreed to last Thursday';

        $shortened = MessageShortener::to($message, 40);

        $this->assertLessThanOrEqual(40, mb_strlen($shortened));
        $this->assertStringEndsWith('…', $shortened);
        $this->assertStringNotContainsString('extraordinar…', $shortened);
        $this->assertSame('Remember the extraordinarily…', $shortened);
    }

    #[Test]
    public function a_single_word_longer_than_the_limit_is_cut_anyway(): void
    {
        $shortened = MessageShortener::to(str_repeat('a', 50), 10);

        $this->assertLessThanOrEqual(10, mb_strlen($shortened));
        $this->assertStringEndsWith('…', $shortened);
    }

    #[Test]
    public function it_counts_characters_rather_than_bytes(): void
    {
        $message = str_repeat('é', 200);

        $this->assertLessThanOrEqual(140, mb_strlen(MessageShortener::to($message, 140)));
    }

    #[Test]
    public function it_trims_what_is_handed_to_it(): void
    {
        $this->assertSame('Birthday.', MessageShortener::to("  Birthday.  \n", 140));
    }
}
