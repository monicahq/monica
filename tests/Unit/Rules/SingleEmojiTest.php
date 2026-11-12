<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\SingleEmoji;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SingleEmojiTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function emojis(): array
    {
        return [
            'a house' => ['🏠'],
            'a sofa written with its presentation selector' => ['🛋️'],
            'a family joined into one picture' => ['👨‍👩‍👧'],
            'a flag' => ['🇺🇸'],
            'a keycap' => ['#️⃣'],
            'a skin tone' => ['👋🏽'],
        ];
    }

    /** @return array<string, array{0: mixed}> */
    public static function notOneEmoji(): array
    {
        return [
            'two letters' => ['ab'],
            'one letter' => ['a'],
            'a digit' => ['3'],
            'two emojis' => ['🏠🏠'],
            'an emoji and a word' => ['🏠 Scranton'],
            'an array' => [['🏠']],
        ];
    }

    #[Test]
    #[DataProvider('emojis')]
    public function it_accepts_one_emoji(string $emoji): void
    {
        $this->assertTrue($this->passes($emoji));
    }

    #[Test]
    #[DataProvider('notOneEmoji')]
    public function it_refuses_anything_but_one_emoji(mixed $value): void
    {
        $this->assertFalse($this->passes($value));
    }

    private function passes(mixed $value): bool
    {
        return Validator::make(['emoji' => $value], ['emoji' => [new SingleEmoji]])->passes();
    }
}
