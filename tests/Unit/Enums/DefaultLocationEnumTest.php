<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\DefaultLocationEnum;
use App\Rules\SingleEmoji;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DefaultLocationEnumTest extends TestCase
{
    #[Test]
    public function it_gives_every_location_an_emoji_the_location_form_accepts(): void
    {
        foreach (DefaultLocationEnum::cases() as $case) {
            $validator = Validator::make(['emoji' => $case->emoji()], ['emoji' => [new SingleEmoji]]);

            $this->assertTrue($validator->passes(), $case->label());
        }
    }
}
